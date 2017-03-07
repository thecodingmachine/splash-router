<?php

namespace Mouf\Mvc\Splash\Routers;

use Cache\Adapter\Void\VoidCachePool;
use Interop\Container\ContainerInterface;
use Interop\Http\Middleware\DelegateInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use Mouf\Mvc\Splash\Controllers\Http400HandlerInterface;
use Mouf\Mvc\Splash\Controllers\Http404HandlerInterface;
use Mouf\Mvc\Splash\Controllers\Http500HandlerInterface;
use Mouf\Mvc\Splash\Exception\BadRequestException;
use Mouf\Mvc\Splash\Exception\PageNotFoundException;
use Mouf\Mvc\Splash\Services\ParameterFetcher;
use Mouf\Mvc\Splash\Services\ParameterFetcherRegistry;
use Mouf\Mvc\Splash\Services\UrlProviderInterface;
use Mouf\Mvc\Splash\Utils\SplashException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Mouf\Mvc\Splash\Store\SplashUrlNode;
use Psr\Log\LoggerInterface;
use Mouf\Mvc\Splash\Services\SplashRequestContext;
use Mouf\Mvc\Splash\Services\SplashUtils;
use Psr\Log\NullLogger;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Stratigility\MiddlewareInterface;

class SplashDefaultRouter implements MiddlewareInterface, ServerMiddlewareInterface
{
    /**
     * The container that will be used to fetch controllers.
     *
     * @var ContainerInterface
     */
    private $container;

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
     * @var CacheItemPoolInterface
     */
    private $cachePool;

    /**
     * The default mode for Splash. Can be one of 'weak' (controllers are allowed to output HTML), or 'strict' (controllers
     * are requested to return a ResponseInterface object).
     *
     * @var string
     */
    private $mode;

    /**
     * In debug mode, Splash will display more accurate messages if output starts (in strict mode).
     *
     * @var bool
     */
    private $debug;

    /**
     * @var ParameterFetcher[]
     */
    private $parameterFetcherRegistry;

    /**
     * The base URL of the application (from which the router will start routing).
     *
     * @var string
     */
    private $rootUrl;

    /**
     * (optional) Handles HTTP 400 status code.
     *
     * @var Http400HandlerInterface
     */
    private $http400Handler;

    /**
     * (optional) Handles HTTP 404 status code (if no $out provided).
     *
     * @var Http404HandlerInterface
     */
    private $http404Handler;

    /**
     * (optional) Handles HTTP 500 status code.
     *
     * @var Http500HandlerInterface
     */
    private $http500Handler;

    /**
     * @Important
     *
     * @param ContainerInterface       $container                The container that will be used to fetch controllers.
     * @param UrlProviderInterface[]   $routeProviders
     * @param ParameterFetcherRegistry $parameterFetcherRegistry
     * @param CacheItemPoolInterface   $cachePool                Splash uses the cache service to store the URL mapping (the mapping between a URL and its controller/action)
     * @param LoggerInterface          $log                      The logger used by Splash
     * @param string                   $mode                     The default mode for Splash. Can be one of 'weak' (controllers are allowed to output HTML), or 'strict' (controllers are requested to return a ResponseInterface object).
     * @param bool                     $debug                    In debug mode, Splash will display more accurate messages if output starts (in strict mode)
     * @param string                   $rootUrl
     */
    public function __construct(ContainerInterface $container, array $routeProviders, ParameterFetcherRegistry $parameterFetcherRegistry, CacheItemPoolInterface $cachePool = null, LoggerInterface $log = null, string $mode = SplashUtils::MODE_STRICT, bool $debug = true, string $rootUrl = '/')
    {
        $this->container = $container;
        $this->routeProviders = $routeProviders;
        $this->parameterFetcherRegistry = $parameterFetcherRegistry;
        $this->cachePool = $cachePool === null ? new VoidCachePool() : $cachePool;
        $this->log = $log === null ? new NullLogger() : $log;
        $this->mode = $mode;
        $this->debug = $debug;
        $this->rootUrl = rtrim($rootUrl, '/').'/';
    }

    /**
     * @param Http400HandlerInterface $http400Handler
     */
    public function setHttp400Handler($http400Handler)
    {
        $this->http400Handler = $http400Handler;

        return $this;
    }

    /**
     * @param Http404HandlerInterface $http404Handler
     */
    public function setHttp404Handler($http404Handler)
    {
        $this->http404Handler = $http404Handler;

        return $this;
    }

    /**
     * @param Http500HandlerInterface $http500Handler
     */
    public function setHttp500Handler($http500Handler)
    {
        $this->http500Handler = $http500Handler;

        return $this;
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
        try {
            return $this->route($request, $response, $out);
        } catch (BadRequestException $e) {
            if ($this->http400Handler !== null) {
                return $this->http400Handler->badRequest($e, $request);
            } else {
                throw $e;
            }
        } catch (PageNotFoundException $e) {
            if ($this->http404Handler !== null) {
                return $this->http404Handler->pageNotFound($request);
            } else {
                throw $e;
            }
        } catch (\Throwable $t) {
            if ($this->http500Handler !== null) {
                return $this->http500Handler->serverError($t, $request);
            } else {
                throw $t;
            }
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable|null          $out
     *
     * @return ResponseInterface
     */
    private function route(ServerRequestInterface $request, ResponseInterface $response, callable $out = null, $retry = false) : ResponseInterface
    {
        $this->purgeExpiredRoutes();

        $urlNodesCacheItem = $this->cachePool->getItem('splashUrlNodes');
        if (!$urlNodesCacheItem->isHit()) {
            // No value in cache, let's get the URL nodes
            $urlsList = $this->getSplashActionsList();
            $urlNodes = $this->generateUrlNode($urlsList);
            $urlNodesCacheItem->set($urlNodes);
            $this->cachePool->save($urlNodesCacheItem);
        }

        $urlNodes = $urlNodesCacheItem->get();
        /* @var $urlNodes SplashUrlNode */

        $request_path = $request->getUri()->getPath();

        $pos = strpos($request_path, $this->rootUrl);
        if ($pos === false) {
            throw new SplashException('Error: the prefix of the web application "'.$this->rootUrl.'" was not found in the URL. The application must be misconfigured. Check the ROOT_URL parameter in your config.php file at the root of your project. It should have the same value as the RewriteBase parameter in your .htaccess file. Requested URL : "'.$request_path.'"');
        }

        $tailing_url = substr($request_path, $pos + strlen($this->rootUrl));
        $tailing_url = urldecode($tailing_url);
        $splashRoute = $urlNodes->walk($tailing_url, $request);

        if ($splashRoute === null) {
            // No route found. Let's try variants with or without trailing / if we are in a GET.
            if ($request->getMethod() === 'GET') {
                // If there is a trailing /, let's remove it and retry
                if (strrpos($tailing_url, '/') === strlen($tailing_url) - 1) {
                    $url = substr($tailing_url, 0, -1);
                    $splashRoute = $urlNodes->walk($url, $request);
                } else {
                    $url = $tailing_url.'/';
                    $splashRoute = $urlNodes->walk($url, $request);
                }

                if ($splashRoute !== null) {
                    // If a route does match, let's make a redirect.
                    return new RedirectResponse($this->rootUrl.$url);
                }
            }

            if ($this->debug === false || $retry === true) {
                // No route found, let's pass control to the next middleware.
                if ($out !== null) {
                    return $out($request, $response);
                } else {
                    $this->log->debug('Found no route for URL {url}.', [
                        'url' => $request_path,
                    ]);
                    throw PageNotFoundException::create($tailing_url);
                }
            } else {
                // We have a 404, but we are in debug mode and have not retried yet...
                // Let's purge the cache and retry!
                $this->purgeUrlsCache();

                return $this->route($request, $response, $out, true);
            }
        }

        // Is the route still valid according to the cache?
        if (!$splashRoute->isCacheValid()) {
            // The route is invalid! Let's purge the cache and retry!
            $this->purgeUrlsCache();

            return $this($request, $response, $out);
        }

        $controller = $this->container->get($splashRoute->getControllerInstanceName());
        $action = $splashRoute->getMethodName();

        $this->log->debug('Routing URL {url} to controller instance {controller} and action {action}', [
            'url' => $request_path,
            'controller' => $splashRoute->getControllerInstanceName(),
            'action' => $action,
        ]);

        $filters = $splashRoute->getFilters();

        $middlewareCaller = function (ServerRequestInterface $request, ResponseInterface $response) use ($controller, $action, $splashRoute) {
            // Let's recreate a new context object (because request can be modified by the filters)
            $context = new SplashRequestContext($request);
            $context->setUrlParameters($splashRoute->getFilledParameters());
            // Let's pass everything to the controller:
            $args = $this->parameterFetcherRegistry->toArguments($context, $splashRoute->getParameters());

            try {
                $response = SplashUtils::buildControllerResponse(
                    function () use ($controller, $action, $args) {
                        return $controller->$action(...$args);
                    },
                    $this->mode,
                    $this->debug
                );
            } catch (SplashException $e) {
                throw new SplashException($e->getMessage(). ' (in '.$splashRoute->getControllerInstanceName().'->'.$splashRoute->getMethodName().')', $e->getCode(), $e);
            }
            return $response;
        };

        // Apply filters
        for ($i = count($filters) - 1; $i >= 0; --$i) {
            $filter = $filters[$i];
            $middlewareCaller = function (ServerRequestInterface $request, ResponseInterface $response) use ($middlewareCaller, $filter) {
                return $filter($request, $response, $middlewareCaller, $this->container);
            };
        }

        $response = $middlewareCaller($request, $response);

        return $response;
    }

    /**
     * Purges the cache if one of the url providers tells us to.
     */
    private function purgeExpiredRoutes()
    {
        $expireTag = '';
        foreach ($this->routeProviders as $routeProvider) {
            /* @var $routeProvider UrlProviderInterface */
            $expireTag .= $routeProvider->getExpirationTag();
        }

        $value = md5($expireTag);

        $urlNodesCacheItem = $this->cachePool->getItem('splashExpireTag');

        if ($urlNodesCacheItem->isHit() && $urlNodesCacheItem->get() === $value) {
            return;
        }

        $this->purgeUrlsCache();

        $urlNodesCacheItem->set($value);
        $this->cachePool->save($urlNodesCacheItem);
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
     */
    public function purgeUrlsCache()
    {
        $this->cachePool->deleteItem('splashUrlNodes');
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        // create a dummy response to keep compatilbility with old middlewares.
        $response = new Response();

        return $this($request, $response, function($request) use ($delegate) {
            return $delegate->process($request);
        });
    }
}
