<?php

namespace Mouf\Mvc\Splash\Services;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Mouf\Mvc\Splash\Fixtures\TestController;
use Mouf\Mvc\Splash\Fixtures\TestController2;
use Mouf\Picotainer\Picotainer;

class ControllerRegistryTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $loader = require __DIR__.'../../../../../../vendor/autoload.php';
        AnnotationRegistry::registerLoader(array($loader, 'loadClass'));
    }

    public function testControllerRegistry()
    {
        $container = new Picotainer([
           'controller' => function () {
               return new TestController();
           },
        ]);

        $parameterFetcherRegistry = ParameterFetcherRegistry::buildDefaultControllerRegistry();
        $controllerRegistry = new ControllerRegistry($container, $parameterFetcherRegistry, new AnnotationReader());
        $controllerRegistry->addController('controller');

        $urlsList = $controllerRegistry->getUrlsList('foo');

        $this->assertCount(1, $urlsList);
        $this->assertInstanceOf(SplashRoute::class, $urlsList[0]);
        $this->assertEquals('myurl', $urlsList[0]->getUrl());
    }

    public function testControllerRegistryThisParam()
    {
        $container = new Picotainer([
            'controller' => function () {
                return new TestController2();
            },
        ]);

        $parameterFetcherRegistry = ParameterFetcherRegistry::buildDefaultControllerRegistry();
        $controllerRegistry = new ControllerRegistry($container, $parameterFetcherRegistry, new AnnotationReader(), ['controller']);

        $urlsList = $controllerRegistry->getUrlsList('foo');

        $this->assertCount(5, $urlsList);
        $this->assertInstanceOf(SplashRoute::class, $urlsList[0]);
        $this->assertEquals('url/42/foo/52', $urlsList[0]->getUrl());

        $this->assertInstanceOf(SplashRoute::class, $urlsList[1]);
        $this->assertEquals('controller/actionAnnotation', $urlsList[1]->getUrl());

        $this->assertInstanceOf(SplashRoute::class, $urlsList[2]);
        $this->assertEquals('controller/', $urlsList[2]->getUrl());
        $this->assertInstanceOf(SplashRoute::class, $urlsList[2]);
        $this->assertEquals('Main page', $urlsList[2]->getTitle());
        $this->assertContains('GET', $urlsList[2]->getHttpMethods());
        $this->assertContains('POST', $urlsList[2]->getHttpMethods());
        $this->assertContains('PUT', $urlsList[2]->getHttpMethods());
        $this->assertContains('DELETE', $urlsList[2]->getHttpMethods());
    }
}
