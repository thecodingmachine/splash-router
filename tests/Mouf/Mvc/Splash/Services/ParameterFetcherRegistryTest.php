<?php

namespace Mouf\Mvc\Splash\Services;

use Mouf\Mvc\Splash\Fixtures\TestController2;
use ReflectionMethod;
use Zend\Diactoros\ServerRequest;

class ParameterFetcherRegistryTest extends \PHPUnit_Framework_TestCase
{
    public function testRegistry()
    {
        $registry = new ParameterFetcherRegistry();
        $registry->registerParameterFetcher(new SplashRequestParameterFetcher());
        $registry->registerParameterFetcher(new SplashRequestFetcher());

        $map = $registry->mapParameters(new ReflectionMethod(TestController2::class, 'completeTest'));

        $this->assertEquals(1, $map[0]['fetcherId']);
        $this->assertEquals(0, $map[1]['fetcherId']);
        $this->assertEquals(1, $map[2]['fetcherId']);
        $this->assertEquals(1, $map[3]['fetcherId']);

        // Now, let's use that map to retrieve data.
        $request = new ServerRequest([], [], '/foo/12/bar', 'GET', 'php://input',
            [],
            [],
            ['id' => 42]
        );
        $splashContext = new SplashRequestContext($request);
        $splashContext->addUrlParameter('var', 'var');

        $arguments = $registry->toArguments($splashContext, $map);

        $this->assertEquals(42, $arguments[0]);
        $this->assertSame($request, $arguments[1]);
        $this->assertEquals('var', $arguments[2]);
        $this->assertEquals(42, $arguments[3]);
    }
}
