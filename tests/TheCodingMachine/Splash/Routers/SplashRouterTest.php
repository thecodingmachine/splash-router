<?php

namespace TheCodingMachine\Splash\Routers;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TheCodingMachine\Splash\Controllers\BadRequestController;
use TheCodingMachine\Splash\Exception\SplashMissingParameterException;
use TheCodingMachine\Splash\Fixtures\TestBadParamController;
use TheCodingMachine\Splash\Fixtures\TestController2;
use TheCodingMachine\Splash\Fixtures\TestExtendedController2;
use TheCodingMachine\Splash\Fixtures\TestFilteredController;
use TheCodingMachine\Splash\Services\ControllerAnalyzer;
use TheCodingMachine\Splash\Services\ControllerRegistry;
use TheCodingMachine\Splash\Services\ParameterFetcherRegistry;
use TheCodingMachine\Splash\Services\SplashUtils;
use TheCodingMachine\Splash\Utils\SplashException;
use Mouf\Picotainer\Picotainer;
use Psr\Cache\CacheItemPoolInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\ServerRequest;
use TheCodingMachine\Splash\Fixtures\TestController3;

class SplashRouterTest extends TestCase
{
    protected function setUp()
    {
        $loader = require __DIR__.'/../../../../vendor/autoload.php';
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
        $controllerAnalyzer = new ControllerAnalyzer($container, $parameterFetcherRegistry, new AnnotationReader());
        $controllerRegistry = new ControllerRegistry($controllerAnalyzer, ['controller']);
        $defaultRouter = new SplashRouter($container, [
            $controllerRegistry,
        ], $parameterFetcherRegistry);

        $request = new ServerRequest([], [], '/foo/var/bar', 'GET', 'php://input',
            [],
            [],
            ['id' => 42]
            );
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new HtmlResponse("");
            }
        };
        $response = $defaultRouter->process($request, $handler);
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
        $response = $defaultRouter->process($request, $handler);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/foo/var/bar', $response->getHeader('Location')[0]);

        // Now, let's test the second kind of redirect
        $request = new ServerRequest([], [], '/controller', 'GET');
        $response = $defaultRouter->process($request, $handler);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/controller/', $response->getHeader('Location')[0]);
    }

    public function testUnknownRoute()
    {
        $container = new Picotainer([
        ]);
        $parameterFetcherRegistry = ParameterFetcherRegistry::buildDefaultControllerRegistry();
        $defaultRouter = new SplashRouter($container, [], $parameterFetcherRegistry);

        $request = new ServerRequest([], [], '/foo', 'GET');
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new HtmlResponse('Not found', 404);
            }
        };
        $response = $defaultRouter->process($request, $handler);
        $this->assertInstanceOf(HtmlResponse::class, $response);
        /* @var $response HtmlResponse */
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Not found', (string) $response->getBody());
    }


    public function testEmojiRoute()
    {
        $container = new Picotainer([
            'controller' => function () {
            return new TestController3();
            },
            ]);
        $parameterFetcherRegistry = ParameterFetcherRegistry::buildDefaultControllerRegistry();
        $controllerAnalyzer = new ControllerAnalyzer($container, $parameterFetcherRegistry, new AnnotationReader());
        $controllerRegistry = new ControllerRegistry($controllerAnalyzer, ['controller']);
        $defaultRouter = new SplashRouter($container, [
            $controllerRegistry,
        ], $parameterFetcherRegistry);
        
        $request = new ServerRequest([], [], '/'.urlencode('🍕'), 'GET');
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new HtmlResponse("ok");
            }
        };
        $response = $defaultRouter->process($request, $handler);
        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testRootUrlError()
    {
        $container = new Picotainer([
        ]);
        $parameterFetcherRegistry = ParameterFetcherRegistry::buildDefaultControllerRegistry();
        $defaultRouter = new SplashRouter($container, [], $parameterFetcherRegistry, null, null, SplashUtils::MODE_STRICT, true, '/baseUrl/');

        $request = new ServerRequest([], [], '/foo', 'GET');
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new HtmlResponse("ok");
            }
        };
        $this->expectException(SplashException::class);
        $response = $defaultRouter->process($request, $handler);
    }

    public function testMissingCompulsoryParameter()
    {
        $container = new Picotainer([
            'controller' => function () {
                return new TestController2();
            },
        ]);
        $parameterFetcherRegistry = ParameterFetcherRegistry::buildDefaultControllerRegistry();
        $controllerAnalyzer = new ControllerAnalyzer($container, $parameterFetcherRegistry, new AnnotationReader());
        $controllerRegistry = new ControllerRegistry($controllerAnalyzer, ['controller']);
        $defaultRouter = new SplashRouter($container, [
            $controllerRegistry,
        ], $parameterFetcherRegistry);

        // We need an ID parameter
        $request = new ServerRequest([], [], '/foo/var/bar', 'GET');
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new HtmlResponse("ok");
            }
        };
        $this->expectException(SplashMissingParameterException::class);
        $response = $defaultRouter->process($request, $handler);
    }

    public function testMissingCompulsoryParameterWithHandler()
    {
        $container = new Picotainer([
            'controller' => function () {
                return new TestController2();
            },
        ]);
        $parameterFetcherRegistry = ParameterFetcherRegistry::buildDefaultControllerRegistry();
        $controllerAnalyzer = new ControllerAnalyzer($container, $parameterFetcherRegistry, new AnnotationReader());
        $controllerRegistry = new ControllerRegistry($controllerAnalyzer, ['controller']);
        $defaultRouter = new SplashRouter($container, [
            $controllerRegistry,
        ], $parameterFetcherRegistry);

        $defaultRouter->setHttp400Handler(new BadRequestController());

        // We need an ID parameter
        $request = new ServerRequest([], [], '/foo/var/bar', 'GET');
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new HtmlResponse("ok");
            }
        };
        $response = $defaultRouter->process($request, $handler);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testPurgeUrlCache()
    {
        $cache = $this->prophesize(CacheItemPoolInterface::class);
        $cache->deleteItem('splashUrlNodes')->shouldBeCalled();

        $container = new Picotainer([]);
        $parameterFetcherRegistry = ParameterFetcherRegistry::buildDefaultControllerRegistry();
        $defaultRouter = new SplashRouter($container, [], $parameterFetcherRegistry, $cache->reveal());
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
        $controllerAnalyzer = new ControllerAnalyzer($container, $parameterFetcherRegistry, new AnnotationReader());
        $controllerRegistry = new ControllerRegistry($controllerAnalyzer, ['controller']);
        $defaultRouter = new SplashRouter($container, [
            $controllerRegistry,
        ], $parameterFetcherRegistry);

        $request = new ServerRequest([], [], '/foo', 'GET');
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new HtmlResponse("");
            }
        };
        $response = $defaultRouter->process($request, $handler);
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
        $controllerAnalyzer = new ControllerAnalyzer($container, $parameterFetcherRegistry, new AnnotationReader());
        $controllerRegistry = new ControllerRegistry($controllerAnalyzer, ['controller']);
        $defaultRouter = new SplashRouter($container, [
            $controllerRegistry,
        ], $parameterFetcherRegistry, new ArrayCachePool());

        $request = new ServerRequest([], [], '/foo/var/bar', 'GET', 'php://input',
            [],
            [],
            ['id' => 42]
        );
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new HtmlResponse("ok");
            }
        };
        $response = $defaultRouter->process($request, $handler);
        $this->assertInstanceOf(JsonResponse::class, $response);

        // Now, let's make another request (this time, we should go through the cache with unchanged tag)
        $response2 = $defaultRouter->process($request, $handler);
        $this->assertInstanceOf(JsonResponse::class, $response2);
    }

    public function testExtendedController()
    {
        $container = new Picotainer([
            'controller' => function () {
                return new TestExtendedController2();
            },
        ]);
        $parameterFetcherRegistry = ParameterFetcherRegistry::buildDefaultControllerRegistry();
        $controllerAnalyzer = new ControllerAnalyzer($container, $parameterFetcherRegistry, new AnnotationReader());
        $controllerRegistry = new ControllerRegistry($controllerAnalyzer, ['controller']);
        $defaultRouter = new SplashRouter($container, [
            $controllerRegistry,
        ], $parameterFetcherRegistry, new ArrayCachePool());

        $request = new ServerRequest([], [], '/url/42/foo/52', 'GET', 'php://input',
            [],
            [],
            []
        );
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new HtmlResponse("");
            }
        };
        $response = $defaultRouter->process($request, $handler);
        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testBadParam()
    {
        $container = new Picotainer([
            'controller' => function () {
                return new TestBadParamController();
            },
        ]);
        $parameterFetcherRegistry = ParameterFetcherRegistry::buildDefaultControllerRegistry();
        $controllerAnalyzer = new ControllerAnalyzer($container, $parameterFetcherRegistry, new AnnotationReader());
        $controllerRegistry = new ControllerRegistry($controllerAnalyzer, ['controller']);
        $defaultRouter = new SplashRouter($container, [
            $controllerRegistry,
        ], $parameterFetcherRegistry, new ArrayCachePool());

        $request = new ServerRequest([], [], '/notexistparam/42', 'GET', 'php://input',
            [],
            [],
            []
        );
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new HtmlResponse("");
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $response = $defaultRouter->process($request, $handler);

    }
}
