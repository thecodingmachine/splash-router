<?php


namespace Mouf\Mvc\Splash\Services;


use Mouf\Mvc\Splash\Fixtures\TestController;
use Mouf\Mvc\Splash\Fixtures\TestController2;
use Mouf\Mvc\Splash\Fixtures\TestControllerDoubleTitle;
use Mouf\Mvc\Splash\Utils\SplashException;
use Mouf\Picotainer\Picotainer;

class ControllerRegistryTest extends \PHPUnit_Framework_TestCase
{
    public function testControllerRegistry()
    {
        $container = new Picotainer([
           "controller" => function() {
               return new TestController();
           }
        ]);

        $parameterFetcherRegistry = ParameterFetcherRegistry::buildDefaultControllerRegistry();
        $controllerRegistry = new ControllerRegistry($container, $parameterFetcherRegistry);
        $controllerRegistry->addController('controller');

        $urlsList = $controllerRegistry->getUrlsList('foo');

        $this->assertCount(1, $urlsList);
        $this->assertInstanceOf(SplashRoute::class, $urlsList[0]);
        $this->assertEquals('myurl', $urlsList[0]->url);
    }

    public function testControllerRegistryThisParam()
    {
        $container = new Picotainer([
            "controller" => function() {
                return new TestController2();
            }
        ]);

        $parameterFetcherRegistry = ParameterFetcherRegistry::buildDefaultControllerRegistry();
        $controllerRegistry = new ControllerRegistry($container, $parameterFetcherRegistry, ['controller']);

        $urlsList = $controllerRegistry->getUrlsList('foo');

        $this->assertCount(4, $urlsList);
        $this->assertInstanceOf(SplashRoute::class, $urlsList[0]);
        $this->assertEquals('url/42/foo/52', $urlsList[0]->url);

        $this->assertInstanceOf(SplashRoute::class, $urlsList[1]);
        $this->assertEquals('controller/actionAnnotation', $urlsList[1]->url);

        $this->assertInstanceOf(SplashRoute::class, $urlsList[2]);
        $this->assertEquals('controller/', $urlsList[2]->url);
        $this->assertInstanceOf(SplashRoute::class, $urlsList[2]);
        $this->assertEquals('Main page', $urlsList[2]->title);
        $this->assertContains('GET', $urlsList[2]->httpMethods );
        $this->assertContains('POST', $urlsList[2]->httpMethods );
        $this->assertContains('PUT', $urlsList[2]->httpMethods );
        $this->assertContains('DELETE', $urlsList[2]->httpMethods );

    }

    public function testDoubleTitle()
    {
        $container = new Picotainer([
            "controller" => function() {
                return new TestControllerDoubleTitle();
            }
        ]);

        $parameterFetcherRegistry = ParameterFetcherRegistry::buildDefaultControllerRegistry();
        $controllerRegistry = new ControllerRegistry($container, $parameterFetcherRegistry, ['controller']);

        $this->expectException(SplashException::class);
        $urlsList = $controllerRegistry->getUrlsList('foo');
    }
}
