<?php


namespace Mouf\Mvc\Splash\Services;


use Doctrine\Common\Annotations\AnnotationReader;
use Mouf\Mvc\Splash\Fixtures\TestAction;
use Mouf\Mvc\Splash\Fixtures\TestController2;
use Mouf\Mvc\Splash\Fixtures\TestFilter;
use Mouf\Picotainer\Picotainer;

class ControllerAnalyzerTest extends \PHPUnit_Framework_TestCase
{
    public function testControllerRegistryWithDetector()
    {
        $container = new Picotainer([]);

        $parameterFetcherRegistry = ParameterFetcherRegistry::buildDefaultControllerRegistry();

        $controllerAnalyzer = new ControllerAnalyzer($container, $parameterFetcherRegistry, new AnnotationReader());


        $this->assertTrue($controllerAnalyzer->isController(TestController2::class));
        $this->assertTrue($controllerAnalyzer->isController(TestAction::class));
        $this->assertFalse($controllerAnalyzer->isController(TestFilter::class));
    }
}
