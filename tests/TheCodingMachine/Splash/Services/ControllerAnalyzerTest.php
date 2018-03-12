<?php

namespace TheCodingMachine\Splash\Services;

use Doctrine\Common\Annotations\AnnotationReader;
use PHPUnit\Framework\TestCase;
use TheCodingMachine\Splash\Fixtures\TestAction;
use TheCodingMachine\Splash\Fixtures\TestController2;
use TheCodingMachine\Splash\Fixtures\TestFilter;
use Mouf\Picotainer\Picotainer;

class ControllerAnalyzerTest extends TestCase
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
