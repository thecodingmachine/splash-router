<?php

namespace TheCodingMachine\Splash\DI;

use Doctrine\Common\Annotations\Reader;
use Interop\Container\ContainerInterface;
use Interop\Container\Factories\Parameter;
use Interop\Container\ServiceProviderInterface;
use TheCodingMachine\Splash\Routers\SplashRouter;
use TheCodingMachine\Splash\Services\ControllerAnalyzer;
use TheCodingMachine\Splash\Services\ControllerRegistry;
use TheCodingMachine\Splash\Services\ParameterFetcherRegistry;
use TheCodingMachine\Splash\Services\SplashRequestFetcher;
use TheCodingMachine\Splash\Services\SplashRequestParameterFetcher;
use TheCodingMachine\Splash\Services\SplashUtils;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use TheCodingMachine\MiddlewareListServiceProvider;
use TheCodingMachine\MiddlewareOrder;

class SplashServiceProvider implements ServiceProviderInterface
{
    const PACKAGE_NAME = 'thecodingmachine/splash';

    public function getFactories()
    {
        return [
            SplashRouter::class => [self::class, 'createDefaultRouter'],
            'thecodingmachine.splash.route-providers' => [self::class, 'createRouteProviders'],
            ControllerRegistry::class => [self::class, 'createControllerRegistry'],
            ControllerAnalyzer::class => [self::class, 'createControllerAnalyzer'],
            ParameterFetcherRegistry::class => [self::class, 'createParameterFetcherRegistry'],
            'thecodingmachine.splash.parameter-fetchers' => [self::class, 'createParameterFetchers'],
            SplashRequestFetcher::class => [self::class, 'createSplashRequestFetcher'],
            SplashRequestParameterFetcher::class => [self::class, 'createSplashRequestParameterFetcher'],
            'thecodingmachine.splash.mode' => new Parameter(SplashUtils::MODE_STRICT),
            'thecodingmachine.splash.controllers' => new Parameter([])
        ];
    }

    public function getExtensions()
    {
        return [
            MiddlewareListServiceProvider::MIDDLEWARES_QUEUE => [self::class, 'updatePriorityQueue'],
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

        $router = new SplashDefaultRouter($container, $routeProviders, $container->get(ParameterFetcherRegistry::class), $cache, $logger, SplashUtils::MODE_STRICT, true, self::getRootUrl($container));

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
        return new ControllerRegistry($container->get(ControllerAnalyzer::class),
            $container->get('thecodingmachine.splash.controllers'));
    }

    public static function createControllerAnalyzer(ContainerInterface $container) : ControllerAnalyzer
    {
        return new ControllerAnalyzer($container, $container->get(ParameterFetcherRegistry::class),
            $container->get(Reader::class));
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

    public static function updatePriorityQueue(ContainerInterface $container, \SplPriorityQueue $priorityQueue) : \SplPriorityQueue
    {
        $priorityQueue->insert($container->get(SplashRouter::class), MiddlewareOrder::ROUTER);
        return $priorityQueue;
    }
}
