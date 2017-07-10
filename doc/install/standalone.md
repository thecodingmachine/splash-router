Installing Splash 8 in standalone / microframework mode
=======================================================

There are [many ways to install Splash](index.md). In this article, we will show you how to get started with a standalone install (no framework).

In order to work, Splash needs:

Requirements
------------

- A PSR-6 cache
- A dependency injection container
- A PSR-7 middleware pipe we can tap into

All these components are up to you. In this article, we will show you how to get started with:

- [Stash (for a PSR-6 caching library)](http://www.stashphp.com/)
- [Simplex (for a container)](https://github.com/mnapoli/simplex/). Note: Simplex is a Pimple 3 fork that adds compatibility with container-interop and with container-interop/service-providers. This will be hugely useful to speed up container configuration.
- [Stratigility (for PSR-7 middleware piping)](https://github.com/zendframework/zend-stratigility)

Of course, your mileage may vary and you can really use any compatible library here.

Install
-------

Here is a typical `composer.json` file to load all dependencies.

**composer.json**
```json
{
  "autoload": {
      "psr-4": {"Test\\": "src/Test"}
  },
  "require": {
    "mouf/mvc.splash-common": "~8.2",
      "mnapoli/simplex": "~0.2.1",
      "zendframework/zend-diactoros": "^1.3",
      "zendframework/zend-stratigility": "^2.0",
      "tedivm/stash": "~0.14.0",
      "thecodingmachine/doctrine-annotations-universal-module": "~1.0",
      "thecodingmachine/psr-6-doctrine-bridge-universal-module": "~1.0",
      "thecodingmachine/stash-universal-module": "~1.0",
      "thecodingmachine/stratigility-harmony": "~2.0"
  },
  "minimum-stability": "dev",
  "prefer-stable": true
}
```

Note: the *thecodingmachine/xxx-universal-module* packages are service providers that we will use to quickly set up the environment.
Note: the *thecodingmachine/whoops-middleware-universal-module* package is optional. It helps debugging by displaying a nice error screen.

Run `composer install`.

Now, we need to set up the entry point.

**public/index.php**
```php
<?php
require_once __DIR__.'/../vendor/autoload.php';

use Simplex\Container;
use TheCodingMachine\DoctrineAnnotationsServiceProvider;
use TheCodingMachine\DoctrineCacheBridgeServiceProvider;
use TheCodingMachine\MiddlewareListServiceProvider;
use TheCodingMachine\StashServiceProvider;
use TheCodingMachine\StratigilityServiceProvider;
use Mouf\Mvc\Splash\DI\SplashServiceProvider;
use TheCodingMachine\WhoopsMiddlewareServiceProvider;

$container = new Container();

// Let's register all the services we need
$container->register(new DoctrineAnnotationsServiceProvider());
$container->register(new DoctrineCacheBridgeServiceProvider());
$container->register(new MiddlewareListServiceProvider());
$container->register(new StashServiceProvider());
$container->register(new StratigilityServiceProvider());
// Note: You should remove the whoops service provider in production.
$container->register(new WhoopsMiddlewareServiceProvider());

// The Splash service provider will automatically register Splash in the Stratigility middleware pipe.
$container->register(new SplashServiceProvider());

// Let's register a controller in the container:
$container->set('rootController', function() {
    return new \Test\RootController();
});

// The 'thecodingmachine.splash.controllers' entry must contain an array of controller instances.
$container->set('thecodingmachine.splash.controllers', [
    'rootController'
]);

// We assume the 'BASE' environment variable contains the base URL of the application (see .htaccess below)
$container->set('root_url', getenv('BASE'));

// Let's get the PSR-7 server, and let's bootstrap it.
$diactorosServer = $container->get(\Zend\Diactoros\Server::class);
$diactorosServer->listen(new \Zend\Stratigility\NoopFinalHandler());
```

The important part here is:

- Controllers are all declared in the container:
  ```php
  $container->set('rootController', function() {
      return new \Test\RootController();
  });
  ```
  Note: if you are using an autowiring container, the container will automatically create the container for you.
- The `'thecodingmachine.splash.controllers'` container entry must contain an array listing the name of all controllers.

You can review []all available configuration parameters in the Splash service provider documentation](../service-provider.md).

If you are using Apache, you can use the `.htaccess` file below to automatically route.

**public/.htaccess**
```php
<IfModule mod_rewrite.c>
    RewriteEngine On

	# .htaccess RewriteBase related tips courtesy of Symfony 2's skeleton app.

    # Determine the RewriteBase automatically and set it as environment variable.
    # If you are using Apache aliases to do mass virtual hosting or installed the
    # project in a subdirectory, the base path will be prepended to allow proper
    # resolution of the base directory and to redirect to the correct URI. It will
    # work in environments without path prefix as well, providing a safe, one-size
    # fits all solution. But as you do not need it in this case, you can comment
    # the following 2 lines to eliminate the overhead.
    RewriteCond %{REQUEST_URI}::$1 ^(/.+)/(.*)::\2$
    RewriteRule ^(.*) - [E=BASE:%1]

    # If the requested filename exists, simply serve it.
    # We only want to let Apache serve files and not directories.
    RewriteCond %{REQUEST_FILENAME} -f
    RewriteRule .*(^index.php) - [L]

    # Rewrite all other queries to the front controller.
    RewriteRule .? %{ENV:BASE}/index.php [L]
</IfModule>
```
