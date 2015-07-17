<?php

namespace Mouf\Mvc\Splash\Routers;

use Mouf\Mvc\Splash\Services\UrlProviderInterface;
use Mouf\Mvc\Splash\Utils\SplashException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Mouf\Utils\Cache\CacheInterface;
use Mouf\MoufManager;
use Mouf\Mvc\Splash\Store\SplashUrlNode;
use Psr\Log\LoggerInterface;
use Mouf\Mvc\Splash\Controllers\WebServiceInterface;
use Mouf\Mvc\Splash\Services\SplashRequestContext;
use Mouf\Mvc\Splash\Services\SplashUtils;
use Zend\Stratigility\MiddlewareInterface;

class SplashDefaultRouter implements MiddlewareInterface
{

    /**
     * List of objects that provide routes.
     *
     * @var UrlProviderInterface[]
     */
    private $routeProviders = [];

    /**
     * The logger used by Splash.
     *
     * @var LoggerInterface
     */
    private $log;

    /**
     * Splash uses the cache service to store the URL mapping (the mapping between a URL and its controller/action).
     *
     * @var CacheInterface
     */
    private $cacheService;

    /**
     * @Important
     *
     * @param UrlProviderInterface[] $routeProviders
     * @param CacheInterface $cacheService Splash uses the cache service to store the URL mapping (the mapping between a URL and its controller/action)
     * @param LoggerInterface $log The logger used by Splash
     */
    public function __construct(array $routeProviders, CacheInterface $cacheService = null, LoggerInterface $log = null)
    {
        $this->routeProviders = $routeProviders;
        $this->cacheService = $cacheService;
        $this->log = $log;
    }

    /**
     * Process an incoming request and/or response.
     *
     * Accepts a server-side request and a response instance, and does
     * something with them.
     *
     * If the response is not complete and/or further processing would not
     * interfere with the work done in the middleware, or if the middleware
     * wants to delegate to another process, it can use the `$out` callable
     * if present.
     *
     * If the middleware does not return a value, execution of the current
     * request is considered complete, and the response instance provided will
     * be considered the response to return.
     *
     * Alternately, the middleware may return a response instance.
     *
     * Often, middleware will `return $out();`, with the assumption that a
     * later middleware will return a response.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param null|callable          $out
     *
     * @return null|ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $out = null)
    {
        // FIXME: find a better way?
        $splashUrlPrefix = ROOT_URL;

        if ($this->cacheService == null) {
            // Retrieve the split parts
            $urlsList = $this->getSplashActionsList();
            $urlNodes = $this->generateUrlNode($urlsList);
        } else {
            $urlNodes = $this->cacheService->get('splashUrlNodes');
            if ($urlNodes == null) {
                // No value in cache, let's get the URL nodes
                $urlsList = $this->getSplashActionsList();
                $urlNodes = $this->generateUrlNode($urlsList);
                $this->cacheService->set('splashUrlNodes', $urlNodes);
            }
        }

        // TODO: add support for [properties] for injecting any property of the controller in the URL

        $request_path = $request->getUri()->getPath();

        $pos = strpos($request_path, $splashUrlPrefix);
        if ($pos === false) {
            throw new SplashException('Error: the prefix of the web application "'.$splashUrlPrefix.'" was not found in the URL. The application must be misconfigured. Check the ROOT_URL parameter in your config.php file at the root of your project. It should have the same value as the RewriteBase parameter in your .htaccess file. Requested URL : "'.$request_path.'"');
        }

        $tailing_url = substr($request_path, $pos + strlen($splashUrlPrefix));

        $context = new SplashRequestContext($request);
        $splashRoute = $urlNodes->walk($tailing_url, $request);

        if ($splashRoute === null) {
            // No route found, let's pass control to the next middleware.
            return $out($request, $response);
        }

        $controller = MoufManager::getMoufManager()->getInstance($splashRoute->controllerInstanceName);
        $action = $splashRoute->methodName;

        $context->setUrlParameters($splashRoute->filledParameters);

        if ($this->log != null) {
            $this->log->info('Routing user with URL {url} to controller {controller} and action {action}', array(
                'url' => $request_path,
                'controller' => get_class($controller),
                'action' => $action,
            ));
        }

        if ($controller instanceof WebServiceInterface) {
            // FIXME: handle correctly webservices (or remove this exception and handle
            // webservice the way we handle controllers
            $response = SplashUtils::buildControllerResponse(
                function () use ($controller) {
                    $this->handleWebservice($controller);
                }
            );

            return $response;
        } else {
            // Let's pass everything to the controller:
            $args = array();
            foreach ($splashRoute->parameters as $paramFetcher) {
                /* @var $param SplashParameterFetcherInterface */
                try {
                    $args[] = $paramFetcher->fetchValue($context);
                } catch (SplashValidationException $e) {
                    $e->setPrependedMessage("Error while validating parameter '".$paramFetcher->getName()."'");
                    throw $e;
                }
            }

            // Handle action__GET or action__POST method (for legacy code).
            if (method_exists($controller, $action.'__'.$request->getMethod())) {
                $action = $action.'__'.$request->getMethod();
            }

            $filters = $splashRoute->filters;

            // Apply filters
            for ($i = count($filters) - 1; $i >= 0; --$i) {
                $filters[$i]->beforeAction();
            }

            $response = SplashUtils::buildControllerResponse(
                function () use ($controller, $action, $args) {
                    return call_user_func_array(array($controller, $action), $args);
                }
            );

            foreach ($filters as $filter) {
                $filter->afterAction();
            }

            return $response;
        }
    }

    /**
     * Handles the call to the webservice.
     *
     * @param WebServiceInterface $webserviceInstance
     */
    private function handleWebservice(WebServiceInterface $webserviceInstance)
    {
        $url = $webserviceInstance->getWebserviceUri();

        $server = new SoapServer(null, array('uri' => $url));
        $server->setObject($webserviceInstance);
        $server->handle();
    }

    /**
     * Returns the list of all SplashActions.
     * This call is LONG and should be cached.
     *
     * @return array<SplashAction>
     */
    public function getSplashActionsList()
    {
        $urls = array();

        foreach ($this->routeProviders as $routeProvider) {
            /* @var $routeProvider UrlProviderInterface */
            $tmpUrlList = $routeProvider->getUrlsList(null);
            $urls = array_merge($urls, $tmpUrlList);
        }

        return $urls;
    }

    /**
     * Generates the URLNodes from the list of URLS.
     * URLNodes are a very efficient way to know whether we can access our page or not.
     *
     * @param array<SplashAction> $urlsList
     *
     * @return SplashUrlNode
     */
    private function generateUrlNode($urlsList)
    {
        $urlNode = new SplashUrlNode();
        foreach ($urlsList as $splashAction) {
            $urlNode->registerCallback($splashAction);
        }

        return $urlNode;
    }

    /**
     * Purges the urls cache.
     *
     * @throws \Exception
     */
    public function purgeUrlsCache()
    {
        $this->cacheService->purge('splashUrlNodes');
    }
}
