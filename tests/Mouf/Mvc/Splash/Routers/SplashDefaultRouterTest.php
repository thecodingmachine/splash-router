<?php


namespace Mouf\Mvc\Splash\Routers;


use Mouf\Mvc\Splash\Fixtures\TestController2;
use Mouf\Mvc\Splash\Services\ControllerRegistry;
use Mouf\Mvc\Splash\Services\ParameterFetcherRegistry;
use Mouf\Mvc\Splash\Services\SplashUtils;
use Mouf\Mvc\Splash\Utils\SplashException;
use Mouf\Picotainer\Picotainer;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\ServerRequest;

class SplashDefaultRouterTest extends \PHPUnit_Framework_TestCase
{
    public function testRoute()
    {
        $container = new Picotainer([
            "controller" => function() {
                return new TestController2();
            }
        ]);
        $parameterFetcherRegistry = ParameterFetcherRegistry::buildDefaultControllerRegistry();
        $controllerRegistry = new ControllerRegistry($container, $parameterFetcherRegistry, ['controller']);
        $defaultRouter = new SplashDefaultRouter($container, [
            $controllerRegistry
        ], $parameterFetcherRegistry);

        $request = new ServerRequest([], [], '/foo/var/bar', 'GET', 'php://input',
            [],
            [],
            [ 'id' => 42 ]
            );
        $response = new HtmlResponse('');
        $response = $defaultRouter($request, $response);
        $this->assertInstanceOf(JsonResponse::class, $response);
        /* @var $response JsonResponse */
        $decodedResponse = json_decode((string) $response->getBody(), true);
        $this->assertEquals(42, $decodedResponse['id'] );
        $this->assertEquals('var', $decodedResponse['var'] );
        $this->assertEquals(42, $decodedResponse['id2'] );
        $this->assertEquals(42, $decodedResponse['opt'] );

        // Now, let's test the redirect
        $request = new ServerRequest([], [], '/foo/var/bar/', 'GET', 'php://input',
            [],
            [],
            [ 'id' => 42 ]
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
        $response = $defaultRouter($request, $response, function() {
            return new HtmlResponse("Not found", 404);
        });
        $this->assertInstanceOf(HtmlResponse::class, $response);
        /* @var $response HtmlResponse */
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals("Not found", (string) $response->getBody() );
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
}
