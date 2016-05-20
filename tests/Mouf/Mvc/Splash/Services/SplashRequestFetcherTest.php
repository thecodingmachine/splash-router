<?php

namespace Mouf\Mvc\Splash\Services;

use Mouf\Mvc\Splash\Fixtures\TestController;
use Mouf\Reflection\MoufReflectionClass;
use Mouf\Reflection\MoufReflectionMethod;
use Zend\Diactoros\ServerRequest;

class SplashRequestFetcherTest extends \PHPUnit_Framework_TestCase
{
    public function testSplashRequestFetcher()
    {
        $class = new MoufReflectionClass(TestController::class);
        $method = new MoufReflectionMethod($class, 'myAction');
        $params = $method->getParameters();

        $splashRequestFetcher = new SplashRequestFetcher();
        $this->assertTrue($splashRequestFetcher->canHandle($params[0]));
        $this->assertFalse($splashRequestFetcher->canHandle($params[1]));

        $this->assertNull($splashRequestFetcher->getFetcherData($params[0]));

        $request = new ServerRequest();
        $context = new SplashRequestContext($request);

        $this->assertSame($request, $splashRequestFetcher->fetchValue(null, $context));
    }
}
