<?php

namespace Mouf\Mvc\Splash\DI;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\Reader;
use Mouf\Mvc\Splash\Fixtures\TestController2;
use Mouf\Mvc\Splash\Routers\SplashDefaultRouter;
use Simplex\Container;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\ServerRequest;

class SplashServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $loader = require __DIR__.'../../../../../../vendor/autoload.php';
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

        $defaultRouter = $simplex->get(SplashDefaultRouter::class);
        $this->assertInstanceOf(SplashDefaultRouter::class, $defaultRouter);

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
    }
}
