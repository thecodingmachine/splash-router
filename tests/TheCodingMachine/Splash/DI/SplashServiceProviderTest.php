<?php

namespace TheCodingMachine\Splash\DI;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\Reader;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TheCodingMachine\Splash\Fixtures\TestController2;
use TheCodingMachine\Splash\Routers\SplashRouter;
use Simplex\Container;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\ServerRequest;

class SplashServiceProviderTest extends TestCase
{
    protected function setUp()
    {
        $loader = require __DIR__.'/../../../../vendor/autoload.php';
        AnnotationRegistry::registerLoader(array($loader, 'loadClass'));
    }

    public function testDefaultRouter()
    {
        $simplex = new Container();
        $simplex->register(new SplashServiceProvider());

        $simplex[Reader::class] = function () {
            return new AnnotationReader();
        };

        $simplex[TestController2::class] = function () {
            return new TestController2();
        };

        $simplex['thecodingmachine.splash.controllers'] = [
            TestController2::class,
        ];

        $defaultRouter = $simplex->get(SplashRouter::class);
        $this->assertInstanceOf(SplashRouter::class, $defaultRouter);

        // Now, let's test the redirect
        $request = new ServerRequest([], [], '/foo/var/bar/', 'GET', 'php://input',
            [],
            [],
            ['id' => 42]
        );
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new HtmlResponse("", 404);
            }
        };
        $response = $defaultRouter->process($request, $handler);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/foo/var/bar', $response->getHeader('Location')[0]);
    }
}
