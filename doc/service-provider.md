# Universal service provider

Splash provides a service-provider to be easily integrated in any [container-interop/service-provider](https://github.com/container-interop/service-provider) compatible framework/container.

## Registering the service provider

You need to register the [`Mouf\Mvc\Splash\DI\SplashServiceProvider`](src/Mouf/Mvc/Splash/DI/SplashServiceProvider.php) into your container.

If your container supports Puli integration, you have nothing to do. Otherwise, refer to your framework or container's documentation to learn how to register *service providers*.

## Introduction

This service provider will provide a default Splash router.
 
It requires an instance of Doctrine's annotation reader to be available.

Note: you can get a service provider providing a Doctrine annotation reader using the following packages:
 
```
composer require thecodingmachine/doctrine-annotations-universal-module
```

It will use a PSR-6 cache if the cache is available.
Note: you can get a service provider providing a working PSR-6 cache using the following packages:
 
```
composer require thecodingmachine/stash-universal-module
```

This will install Stash and its related service-provider.

## Expected values / services

This *service provider* expects the following configuration / services to be available:

| Name            | Compulsory | Description                            |
|-----------------|------------|----------------------------------------|
| `thecodingmachine.splash.controllers`      | *yes*       | A list of controllers entry identifiers in the container (it is an array of strings, each string is the entry of a controller) |
| `Doctrine\Common\Annotations\Reader`  | *yes*       | An instance of Doctrine's annotation reader.  |
| `CacheItemPoolInterface::class`  | *no*       | The PSR-6 cache pool used to cache the routes  |
| `LoggerInterface::class`  | *no*       | An optional PSR-3 logger |
| `thecodingmachine.splash.mode`  | *no*       | The mode Splash runs into. Can be one of `SplashUtils::MODE_STRICT` or `SplashUtils::MODE_WEAK`. Defaults to `SplashUtils::MODE_STRICT`. |
| `thecodingmachine.splash.debug`  | *no*       | If true, Splash will display an error with the 'echoed' output in strict mode. Defaults to true. |
| `thecodingmachine.splash.root_url` (or `root_url`)  | *no*       | The base URL of the application. Defaults to '/'. |


## Provided services

This *service provider* provides the following services:

| Service name                | Description                          |
|-----------------------------|--------------------------------------|
| `SplashDefaultRouter::class`  | The Splash router  |
| `thecodingmachine.splash.route-providers`  | A list of "route providers" for Splash (an array of `UrlProviderInterface`). Each route provider is in charge of feeding routes to Splash. By default, this array contains an instance of the `ControllerRegistry` that scans routes of the controllers.   |
| `ControllerRegistry::class`  |  Instance of `ControllerRegistry`.  |
| `ParameterFetcherRegistry::class` | Registry class referencing all parameter fetchers |
| `thecodingmachine.splash.parameter-fetchers` | Array of `ParameterFetcher` objects |
| `SplashRequestFetcher::class` | An instance of `SplashRequestFetcher` (to autofill the attributes type-hinted on the `ServerRequestInterface`) |
| `SplashRequestParameterFetcher::class` | An instance of `SplashRequestParameterFetcher` (to autofill attributes from the request) |



## Extended services

This *service provider* registers the `Slim\App` in the `MiddlewareListServiceProvider::MIDDLEWARES_QUEUE`.

| Service name                | Description                          |
|-----------------------------|--------------------------------------|
| `MiddlewareListServiceProvider::MIDDLEWARES_QUEUE`  | Adds the Splash middleware to this queue (to be used by external routers)  |