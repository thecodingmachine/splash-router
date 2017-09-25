<?php

namespace Mouf\Mvc\Splash\DI;

use Doctrine\Common\Annotations\Reader;
use Interop\Container\ContainerInterface;
use Interop\Container\Factories\Parameter;
use Interop\Container\ServiceProviderInterface;
use Mouf\Mvc\Splash\Routers\SplashDefaultRouter;
use Mouf\Mvc\Splash\Services\ControllerAnalyzer;
use Mouf\Mvc\Splash\Services\ControllerRegistry;
use Mouf\Mvc\Splash\Services\ParameterFetcherRegistry;
use Mouf\Mvc\Splash\Services\SplashRequestFetcher;
use Mouf\Mvc\Splash\Services\SplashRequestParameterFetcher;
use Mouf\Mvc\Splash\Services\SplashUtils;
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
            SplashDefaultRouter::class => [self::class, 'createDefaultRouter'],
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
        $priorityQueue->insert($container->get(SplashDefaultRouter::class), MiddlewareOrder::ROUTER);
        return $priorityQueue;
    }
}
