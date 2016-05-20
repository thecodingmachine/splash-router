<?php


namespace Mouf\Mvc\Splash\Services;


use Mouf\Mvc\Splash\Fixtures\TestController2;
use ReflectionMethod;

class ParameterFetcherRegistryTest extends \PHPUnit_Framework_TestCase
{
    public function testRegistry()
    {
        $registry = new ParameterFetcherRegistry();
        $registry->registerParameterFetcher(new SplashRequestParameterFetcher());
        $registry->registerParameterFetcher(new SplashRequestFetcher());


        $map = $registry->mapParameters(new ReflectionMethod(TestController2::class, 'completeTest'));

        $this->assertEquals(1, $map[0]['fetcherId'] );
        $this->assertEquals(0, $map[1]['fetcherId'] );
        $this->assertEquals(1, $map[2]['fetcherId'] );
        $this->assertEquals(1, $map[3]['fetcherId'] );
    }
}
