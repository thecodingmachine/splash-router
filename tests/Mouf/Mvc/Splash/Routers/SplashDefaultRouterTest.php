<?php


namespace Mouf\Mvc\Splash\Routers;


use Mouf\Mvc\Splash\Fixtures\TestController2;
use Mouf\Mvc\Splash\Services\ControllerRegistry;
use Mouf\Mvc\Splash\Services\ParameterFetcherRegistry;
use Mouf\Picotainer\Picotainer;

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
        /*$defaultRouter = new SplashDefaultRouter([
            $controllerRegistry
        ])*/
    }
}
