<?php

namespace TheCodingMachine\Splash\Routers;

use Cache\Adapter\Void\VoidCachePool;
use Interop\Container\ContainerInterface;
use TheCodingMachine\Splash\Controllers\Http400HandlerInterface;
use TheCodingMachine\Splash\Exception\BadRequestException;
use TheCodingMachine\Splash\Exception\PageNotFoundException;
use TheCodingMachine\Splash\Filters\FilterPipe;
use TheCodingMachine\Splash\Services\ParameterFetcher;
use TheCodingMachine\Splash\Services\ParameterFetcherRegistry;
use TheCodingMachine\Splash\Services\UrlProviderInterface;
use TheCodingMachine\Splash\Utils\SplashException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TheCodingMachine\Splash\Store\SplashUrlNode;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use TheCodingMachine\Splash\Services\SplashUtils;
use Psr\Log\NullLogger;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Stratigility\MiddlewarePipe;

class SplashRouter implements MiddlewareInterface
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
    public function __construct(
        ContainerInterface $container,
        array $routeProviders,
        ParameterFetcherRegistry $parameterFetcherRegistry,
        CacheItemPoolInterface $cachePool = null,
        LoggerInterface $log = null,
        string $mode = SplashUtils::MODE_STRICT,
        bool $debug = true,
        string $rootUrl = '/'
    ) {
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
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable|null          $out
     *
     * @return ResponseInterface
     */
    private function route(ServerRequestInterface $request, RequestHandlerInterface $next = null, $retry = false) : ResponseInterface
    {
        $this->purgeExpiredRoutes();

        $urlNodesCacheItem = $this->cachePool->getItem('splashUrlNodes');
        if (!$urlNodesCacheItem->isHit()) {
            // No value in cache, let's get the URL nodes
            $urlsList = $this->getSplashActionsList();
            $urlNodes = $this->generateUrlNode($urlsList);
            $urlNodesCacheItem->set($urlNodes);
            $this->cachePool->save($urlNodesCacheItem);
        } else {
            $urlNodes = $urlNodesCacheItem->get();
        }

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
                if ($next !== null) {
                    return $next->handle($request);
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

                return $this->route($request, $next, true);
            }
        }

        // Is the route still valid according to the cache?
        if (!$splashRoute->isCacheValid()) {
            // The route is invalid! Let's purge the cache and retry!
            $this->purgeUrlsCache();

            return $this->process($request, $next);
        }

        $controller = $this->container->get($splashRoute->getControllerInstanceName());
        $action = $splashRoute->getMethodName();

        $this->log->debug('Routing URL {url} to controller instance {controller} and action {action}', [
            'url' => $request_path,
            'controller' => $splashRoute->getControllerInstanceName(),
            'action' => $action,
        ]);

        $filters = $splashRoute->getFilters();
        $pipeHandler = new ControllerHandler($splashRoute, $controller, $this->parameterFetcherRegistry, $this->mode, $this->debug);
        $filterPipe = new FilterPipe($filters, $pipeHandler, $this->container);
        return $filterPipe->process($request);
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
     *
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $this->route($request, $handler);
        } catch (BadRequestException $e) {
            if ($this->http400Handler !== null) {
                return $this->http400Handler->badRequest($e, $request);
            } else {
                throw $e;
            }
        } catch (PageNotFoundException $e) {
            return $handler->handle($request);
        }
    }
}
