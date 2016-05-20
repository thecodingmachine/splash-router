<?php

namespace Mouf\Mvc\Splash\Services;

use Mouf\Mvc\Splash\Fixtures\TestController;
use ReflectionMethod;
use Zend\Diactoros\ServerRequest;

class SplashRequestParameterFetcherTest extends \PHPUnit_Framework_TestCase
{
    public function testSplashRequestParameterFetcher()
    {
        $method = new ReflectionMethod(TestController::class, 'myAction');
        $params = $method->getParameters();

        $splashRequestFetcher = new SplashRequestParameterFetcher();
        //$this->assertFalse($splashRequestFetcher->canHandle($params[0]));
        $this->assertTrue($splashRequestFetcher->canHandle($params[2]));

        $data = $splashRequestFetcher->getFetcherData($params[2]);
        $this->assertEquals([
            'key' => 'id',
            'compulsory' => false,
            'default' => null,
        ], $data);

        $dataCompulsory = $splashRequestFetcher->getFetcherData($params[1]);
        $this->assertEquals([
            'key' => 'compulsory',
            'compulsory' => true,
        ], $dataCompulsory);

        $request = new ServerRequest(
            [],
            [],
            'toto/tata',
            'GET',
            'php://input',
            [],
            [],
            [
                'id' => 42,
            ]
        );
        $context = new SplashRequestContext($request);

        $this->assertSame(42, $splashRequestFetcher->fetchValue($data, $context));
    }
}
