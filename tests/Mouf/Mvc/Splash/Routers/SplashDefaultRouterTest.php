<?php

namespace Mouf\Mvc\Splash\Routers;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Mouf\Mvc\Splash\Controllers\HttpErrorsController;
use Mouf\Mvc\Splash\Exception\PageNotFoundException;
use Mouf\Mvc\Splash\Exception\SplashMissingParameterException;
use Mouf\Mvc\Splash\Fixtures\TestController2;
use Mouf\Mvc\Splash\Fixtures\TestFilteredController;
use Mouf\Mvc\Splash\Services\ControllerRegistry;
use Mouf\Mvc\Splash\Services\ParameterFetcherRegistry;
use Mouf\Mvc\Splash\Services\SplashUtils;
use Mouf\Mvc\Splash\Utils\SplashException;
use Mouf\Picotainer\Picotainer;
use Psr\Cache\CacheItemPoolInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\ServerRequest;

class SplashDefaultRouterTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $loader = require __DIR__.'../../../../../../vendor/autoload.php';
        AnnotationRegistry::registerLoader(array($loader, 'loadClass'));
    }

    public function testRoute()
    {
        $container = new Picotainer([
            'controller' => function () {
                return new TestController2();
            },
        ]);
        $parameterFetcherRegistry = ParameterFetcherRegistry::buildDefaultControllerRegistry();
        $controllerRegistry = new ControllerRegistry($container, $parameterFetcherRegistry, new AnnotationReader(), ['controller']);
        $defaultRouter = new SplashDefaultRouter($container, [
            $controllerRegistry,
        ], $parameterFetcherRegistry);

        $request = new ServerRequest([], [], '/foo/var/bar', 'GET', 'php://input',
            [],
            [],
            ['id' => 42]
            );
        $response = new HtmlResponse('');
        $response = $defaultRouter($request, $response);
        $this->assertInstanceOf(JsonResponse::class, $response);
        /* @var $response JsonResponse */
        $decodedResponse = json_decode((string) $response->getBody(), true);
        $this->assertEquals(42, $decodedResponse['id']);
        $this->assertEquals('var', $decodedResponse['var']);
        $this->assertEquals(42, $decodedResponse['id2']);
        $this->assertEquals(42, $decodedResponse['opt']);

        // Now, let's test the redirect
        $request = new ServerRequest([], [], '/foo/var/bar/', 'GET', 'php://input',
            [],
            [],
            ['id' => 42]
        );
        $response = new HtmlResponse('');
        $response = $defaultRouter($request, $response);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/foo/var/bar', $response->getHeader('Location')[0]);

        // Now, let's test the second kind of redirect
        $request = new ServerRequest([], [], '/controller', 'GET');
        $response = new HtmlResponse('');
        $response = $defaultRouter($request, $response);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/controller/', $response->getHeader('Location')[0]);
    }

    public function testUnknownRoute()
    {
        $container = new Picotainer([
        ]);
        $parameterFetcherRegistry = ParameterFetcherRegistry::buildDefaultControllerRegistry();
        $defaultRouter = new SplashDefaultRouter($container, [], $parameterFetcherRegistry);

        $request = new ServerRequest([], [], '/foo', 'GET');
        $response = new HtmlResponse('');
        $response = $defaultRouter($request, $response, function () {
            return new HtmlResponse('Not found', 404);
        });
        $this->assertInstanceOf(HtmlResponse::class, $response);
        /* @var $response HtmlResponse */
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Not found', (string) $response->getBody());

        // Now, let's retry without a $out parameter and let's check we get an exception
        $this->expectException(PageNotFoundException::class);
        $response = $defaultRouter($request, $response);
    }

    public function testUnknownRouteWith404Handler()
    {
        $container = new Picotainer([
        ]);
        $parameterFetcherRegistry = ParameterFetcherRegistry::buildDefaultControllerRegistry();
        $defaultRouter = new SplashDefaultRouter($container, [], $parameterFetcherRegistry);
        $errorsController = HttpErrorsController::createDefault();
        $defaultRouter->setHttp404Handler($errorsController);

        $request = new ServerRequest([], [], '/foo', 'GET');
        $response = new HtmlResponse('');
        $response = $defaultRouter($request, $response);
        /* @var $response HtmlResponse */

        // Now, let's retry without a $out parameter and let's check we get an exception
        $response = $defaultRouter($request, $response);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testRootUrlError()
    {
        $container = new Picotainer([
        ]);
        $parameterFetcherRegistry = ParameterFetcherRegistry::buildDefaultControllerRegistry();
        $defaultRouter = new SplashDefaultRouter($container, [], $parameterFetcherRegistry, null, null, SplashUtils::MODE_STRICT, true, '/baseUrl/');

        $request = new ServerRequest([], [], '/foo', 'GET');
        $response = new HtmlResponse('');
        $this->expectException(SplashException::class);
        $response = $defaultRouter($request, $response);
    }

    public function testMissingCompulsoryParameter()
    {
        $container = new Picotainer([
            'controller' => function () {
                return new TestController2();
            },
        ]);
        $parameterFetcherRegistry = ParameterFetcherRegistry::buildDefaultControllerRegistry();
        $controllerRegistry = new ControllerRegistry($container, $parameterFetcherRegistry, new AnnotationReader(), ['controller']);
        $defaultRouter = new SplashDefaultRouter($container, [
            $controllerRegistry,
        ], $parameterFetcherRegistry);

        // We need an ID parameter
        $request = new ServerRequest([], [], '/foo/var/bar', 'GET');
        $response = new HtmlResponse('');
        $this->expectException(SplashMissingParameterException::class);
        $response = $defaultRouter($request, $response);
    }

    public function testMissingCompulsoryParameterWithHandler()
    {
        $container = new Picotainer([
            'controller' => function () {
                return new TestController2();
            },
        ]);
        $parameterFetcherRegistry = ParameterFetcherRegistry::buildDefaultControllerRegistry();
        $controllerRegistry = new ControllerRegistry($container, $parameterFetcherRegistry, new AnnotationReader(), ['controller']);
        $defaultRouter = new SplashDefaultRouter($container, [
            $controllerRegistry,
        ], $parameterFetcherRegistry);

        $errorsController = HttpErrorsController::createDefault();
        $defaultRouter->setHttp400Handler($errorsController);

        // We need an ID parameter
        $request = new ServerRequest([], [], '/foo/var/bar', 'GET');
        $response = new HtmlResponse('');
        $response = $defaultRouter($request, $response);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testExceptionWithHandler()
    {
        $container = new Picotainer([
            'controller' => function () {
                return new TestController2();
            },
        ]);
        $parameterFetcherRegistry = ParameterFetcherRegistry::buildDefaultControllerRegistry();
        $controllerRegistry = new ControllerRegistry($container, $parameterFetcherRegistry, new AnnotationReader(), ['controller']);
        $defaultRouter = new SplashDefaultRouter($container, [
            $controllerRegistry,
        ], $parameterFetcherRegistry);

        $errorsController = HttpErrorsController::createDefault();
        $defaultRouter->setHttp500Handler($errorsController);

        // We need an ID parameter
        $request = new ServerRequest([], [], '/controller/triggerException', 'GET');
        $response = new HtmlResponse('');
        $response = $defaultRouter($request, $response);
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testPurgeUrlCache()
    {
        $cache = $this->prophesize(CacheItemPoolInterface::class);
        $cache->deleteItem('splashUrlNodes')->shouldBeCalled();

        $container = new Picotainer([]);
        $parameterFetcherRegistry = ParameterFetcherRegistry::buildDefaultControllerRegistry();
        $defaultRouter = new SplashDefaultRouter($container, [], $parameterFetcherRegistry, $cache->reveal());
        $defaultRouter->purgeUrlsCache();
    }

    public function testFilters()
    {
        $container = new Picotainer([
            'controller' => function () {
                return new TestFilteredController();
            },
        ]);
        $parameterFetcherRegistry = ParameterFetcherRegistry::buildDefaultControllerRegistry();
        $controllerRegistry = new ControllerRegistry($container, $parameterFetcherRegistry, new AnnotationReader(), ['controller']);
        $defaultRouter = new SplashDefaultRouter($container, [
            $controllerRegistry,
        ], $parameterFetcherRegistry);

        $request = new ServerRequest([], [], '/foo', 'GET');
        $response = new HtmlResponse('');
        $response = $defaultRouter($request, $response);
        $this->assertEquals('42bar', (string) $response->getBody());
    }

    public function testExpirationTag()
    {
        $container = new Picotainer([
            'controller' => function () {
                return new TestController2();
            },
        ]);
        $parameterFetcherRegistry = ParameterFetcherRegistry::buildDefaultControllerRegistry();
        $controllerRegistry = new ControllerRegistry($container, $parameterFetcherRegistry, new AnnotationReader(), ['controller']);
        $defaultRouter = new SplashDefaultRouter($container, [
            $controllerRegistry,
        ], $parameterFetcherRegistry, new ArrayCachePool());

        $request = new ServerRequest([], [], '/foo/var/bar', 'GET', 'php://input',
            [],
            [],
            ['id' => 42]
        );
        $response = new HtmlResponse('');
        $response = $defaultRouter($request, $response);
        $this->assertInstanceOf(JsonResponse::class, $response);

        // Now, let's make another request (this time, we should go through the cache with unchanged etag)
        $response2 = $defaultRouter($request, $response);
        $this->assertInstanceOf(JsonResponse::class, $response2);
    }
}
