<?php

namespace Mouf\Mvc\Splash\DI;

use Doctrine\Common\Annotations\Reader;
use Interop\Container\ContainerInterface;
use Interop\Container\Factories\Parameter;
use Interop\Container\ServiceProvider;
use Mouf\Mvc\Splash\Routers\SplashDefaultRouter;
use Mouf\Mvc\Splash\Services\ControllerRegistry;
use Mouf\Mvc\Splash\Services\ParameterFetcherRegistry;
use Mouf\Mvc\Splash\Services\SplashRequestFetcher;
use Mouf\Mvc\Splash\Services\SplashRequestParameterFetcher;
use Mouf\Mvc\Splash\Services\SplashUtils;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class SplashServiceProvider implements ServiceProvider
{
    const PACKAGE_NAME = 'thecodingmachine/splash';

    /**
     * Returns a list of all container entries registered by this service provider.
     *
     * - the key is the entry name
     * - the value is a callable that will return the entry, aka the **factory**
     *
     * Factories have the following signature:
     *        function(ContainerInterface $container, callable $getPrevious = null)
     *
     * About factories parameters:
     *
     * - the container (instance of `Interop\Container\ContainerInterface`)
     * - a callable that returns the previous entry if overriding a previous entry, or `null` if not
     *
     * @return callable[]
     */
    public function getServices()
    {
        return [
            SplashDefaultRouter::class => [self::class, 'createDefaultRouter'],
            'thecodingmachine.splash.route-providers' => [self::class, 'createRouteProviders'],
            ControllerRegistry::class => [self::class, 'createControllerRegistry'],
            ParameterFetcherRegistry::class => [self::class, 'createParameterFetcherRegistry'],
            'thecodingmachine.splash.parameter-fetchers' => [self::class, 'createParameterFetchers'],
            SplashRequestFetcher::class => [self::class, 'createSplashRequestFetcher'],
            SplashRequestParameterFetcher::class => [self::class, 'createSplashRequestParameterFetcher'],
            'thecodingmachine.splash.mode' => new Parameter(SplashUtils::MODE_STRICT),
        ];
    }

    public static function createDefaultRouter(ContainerInterface $container) : SplashDefaultRouter
    {
        if ($container->has(CacheItemPoolInterface::class)) {
            $cache = $container->get(CacheItemPoolInterface::class);
        } else {
            $cache = null;
        }

        if ($container->has(LoggerInterface::class)) {
            $logger = $container->get(LoggerInterface::class);
        } else {
            $logger = null;
        }

        $routeProviders = $container->get('thecodingmachine.splash.route-providers');

        $router = new SplashDefaultRouter($container, $routeProviders, $container->get(ParameterFetcherRegistry::class), $cache, $logger, self::getRootUrl($container));

        return $router;
    }

    private static function getRootUrl(ContainerInterface $container)
    {
        if ($container->has('thecodingmachine.splash.root_url')) {
            return $container->get('thecodingmachine.splash.root_url');
        } elseif ($container->has('root_url')) {
            return $container->get('root_url');
        } else {
            return '/';
        }
    }

    public static function createRouteProviders(ContainerInterface $container) : array
    {
        return [
            $container->get(ControllerRegistry::class),
        ];
    }

    public static function createControllerRegistry(ContainerInterface $container) : ControllerRegistry
    {
        return new ControllerRegistry($container, $container->get(ParameterFetcherRegistry::class),
            $container->get(Reader::class), $container->get('thecodingmachine.splash.controllers'));
    }

    public static function createParameterFetcherRegistry(ContainerInterface $container) : ParameterFetcherRegistry
    {
        return new ParameterFetcherRegistry($container->get('thecodingmachine.splash.parameter-fetchers'));
    }

    public static function createParameterFetchers(ContainerInterface $container) : array
    {
        return [
            $container->get(SplashRequestFetcher::class),
            $container->get(SplashRequestParameterFetcher::class),
        ];
    }

    public static function createSplashRequestFetcher() : SplashRequestFetcher
    {
        return new SplashRequestFetcher();
    }

    public static function createSplashRequestParameterFetcher() : SplashRequestParameterFetcher
    {
        return new SplashRequestParameterFetcher();
    }
}
