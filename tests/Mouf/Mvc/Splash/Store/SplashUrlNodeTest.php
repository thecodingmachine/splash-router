<?php

namespace Mouf\Mvc\Splash\Store;

use Mouf\Mvc\Splash\Services\SplashRoute;
use Mouf\Mvc\Splash\Utils\SplashException;
use Zend\Diactoros\ServerRequest;

/**
 * A SplashUrlNode is a datastructure optimised to navigate all possible URLs known to the application.
 * A SplashUrlNode represents all possible routes starting at the current position (just after a / in a URL).
 *
 * @author David Negrier
 */
class SplashUrlNodeTest extends \PHPUnit_Framework_TestCase
{
    public function testAddUrl()
    {
        $splashUrlNode = new SplashUrlNode();
        $callback = new SplashRoute('toto/tata', 'myController', 'myMethod', 'myTitle', 'fullComment', array('GET', 'POST'));
        $splashUrlNode->registerCallback($callback);

        $result = $splashUrlNode->walk('toto/tata', new ServerRequest([], [], 'toto/tata', 'GET'));

        /* @var $result SplashRoute */
        $this->assertInstanceOf(SplashRoute::class, $result);
        $this->assertEquals('myController', $result->getControllerInstanceName());
        $this->assertEquals('myMethod', $result->getMethodName());
    }

    public function testTrailingSlashUrl()
    {
        $splashUrlNode = new SplashUrlNode();
        $callback = new SplashRoute('toto/tata/', 'myController', 'myMethod', 'myTitle', 'fullComment', array('GET', 'POST'));
        $splashUrlNode->registerCallback($callback);

        $result = $splashUrlNode->walk('toto/tata/', new ServerRequest([], [], 'toto/tata', 'GET'));
        /* @var $result SplashRoute */
        $this->assertInstanceOf(SplashRoute::class, $result);
        $this->assertEquals('myController', $result->getControllerInstanceName());
        $this->assertEquals('myMethod', $result->getMethodName());
    }

    public function testRootUrl()
    {
        $splashUrlNode = new SplashUrlNode();
        $callback = new SplashRoute('/', 'myController', 'myMethod', 'myTitle', 'fullComment', array('GET', 'POST'));
        $splashUrlNode->registerCallback($callback);

        $result = $splashUrlNode->walk('/', new ServerRequest([], [], '/', 'GET'));
        /* @var $result SplashRoute */
        $this->assertInstanceOf(SplashRoute::class, $result);
        $this->assertEquals('myController', $result->getControllerInstanceName());
        $this->assertEquals('myMethod', $result->getMethodName());
    }

    public function testSameUrls()
    {
        $splashUrlNode = new SplashUrlNode();
        $callback = new SplashRoute('/', 'myController', 'myMethod', 'myTitle', 'fullComment', array('GET', 'POST'));
        $splashUrlNode->registerCallback($callback);
        $callback = new SplashRoute('/', 'myController', 'myMethod', 'myTitle', 'fullComment', array('GET', 'POST'));

        $this->expectException(SplashException::class);
        $splashUrlNode->registerCallback($callback);
    }

    /**
     *
     */
    public function testGlobalUrlCatchGet()
    {
        $splashUrlNode = new SplashUrlNode();
        $callback = new SplashRoute('/toto', 'myController', 'myMethod', 'myTitle', 'fullComment', array());
        $splashUrlNode->registerCallback($callback);

        $result = $splashUrlNode->walk('/toto', new ServerRequest([], [], '/toto', 'GET'));
        /* @var $result SplashRoute */
        $this->assertInstanceOf(SplashRoute::class, $result);
        $this->assertEquals('myController', $result->getControllerInstanceName());
        $this->assertEquals('myMethod', $result->getMethodName());
    }

    /**
     *
     */
    public function testMultiUrls()
    {
        $splashUrlNode = new SplashUrlNode();
        $callback = new SplashRoute('/toto', 'myControllerOk', 'myMethodOk', 'myTitle', 'fullComment', array());
        $splashUrlNode->registerCallback($callback);
        $callback = new SplashRoute('/toto/tata', 'myController', 'myMethod', 'myTitle', 'fullComment', array());
        $splashUrlNode->registerCallback($callback);
        $callback = new SplashRoute('/tata', 'myController', 'myMethod', 'myTitle', 'fullComment', array());
        $splashUrlNode->registerCallback($callback);

        $result = $splashUrlNode->walk('/toto', new ServerRequest([], [], '/toto', 'POST'));
        /* @var $result SplashRoute */
        $this->assertInstanceOf(SplashRoute::class, $result);
        $this->assertEquals('myControllerOk', $result->getControllerInstanceName());
        $this->assertEquals('myMethodOk', $result->getMethodName());
    }

    /**
     *
     */
    public function testParametersUrls()
    {
        $splashUrlNode = new SplashUrlNode();
        $callback = new SplashRoute('/toto/{var}/tata', 'myController', 'myMethod', 'myTitle', 'fullComment', []);
        $splashUrlNode->registerCallback($callback);

        $result = $splashUrlNode->walk('/toto/12/tata', new ServerRequest([], [], '/toto/12/tata', 'POST'));
        /* @var $result SplashRoute */
        $this->assertInstanceOf(SplashRoute::class, $result);
        $this->assertEquals('myController', $result->getControllerInstanceName());
        $this->assertEquals('myMethod', $result->getMethodName());
        $this->assertEquals(12, $result->getFilledParameters()['var']);
    }

    /**
     *
     */
    public function testWildcardUrls()
    {
        $splashUrlNode = new SplashUrlNode();
        $callback = new SplashRoute('/toto/*', 'myController', 'myMethod', 'myTitle', 'fullComment', array());
        $splashUrlNode->registerCallback($callback);
        $callback2 = new SplashRoute('/toto/*', 'myControllerPost', 'myMethodPost', 'myTitle', 'fullComment', array('POST'));
        $splashUrlNode->registerCallback($callback2);

        $result = $splashUrlNode->walk('/toto/tata/titi', new ServerRequest([], [], '/toto', 'GET'));
        /* @var $result SplashRoute */
        $this->assertInstanceOf(SplashRoute::class, $result);
        $this->assertEquals('myController', $result->getControllerInstanceName());
        $this->assertEquals('myMethod', $result->getMethodName());

        $result = $splashUrlNode->walk('/toto/', new ServerRequest([], [], '/toto', 'GET'));
        /* @var $result SplashRoute */
        $this->assertInstanceOf(SplashRoute::class, $result);
        $this->assertEquals('myController', $result->getControllerInstanceName());
        $this->assertEquals('myMethod', $result->getMethodName());

        // Now, let's test an URL with HTTP method set.
        $result = $splashUrlNode->walk('/toto/tata/titi', new ServerRequest([], [], '/toto', 'POST'));
        $this->assertEquals('myControllerPost', $result->getControllerInstanceName());
        $this->assertEquals('myMethodPost', $result->getMethodName());
    }

    public function testUnsupportedWildcard()
    {
        $splashUrlNode = new SplashUrlNode();
        $callback = new SplashRoute('toto/*/tata', 'myController', 'myMethod', 'myTitle', 'myComment');
        $this->expectException(SplashException::class);
        $splashUrlNode->registerCallback($callback);
    }

    public function testDoubleMethod()
    {
        $splashUrlNode = new SplashUrlNode();
        $callback = new SplashRoute('foo/bar', 'myController', 'myMethod', 'myTitle', 'myComment');
        $splashUrlNode->registerCallback($callback);
        $callback2 = new SplashRoute('foo/bar', 'myController2', 'myMethod2', 'myTitle', 'myComment');
        $this->expectException(SplashException::class);
        $splashUrlNode->registerCallback($callback2);
    }

    public function testDoubleWildcardMethod()
    {
        $splashUrlNode = new SplashUrlNode();
        $callback = new SplashRoute('foo/*', 'myController', 'myMethod', 'myTitle', 'myComment');
        $splashUrlNode->registerCallback($callback);
        $callback2 = new SplashRoute('foo/*', 'myController2', 'myMethod2', 'myTitle', 'myComment');
        $this->expectException(SplashException::class);
        $splashUrlNode->registerCallback($callback2);
    }

    public function testDoubleWildcardMethodWithHttpMethod()
    {
        $splashUrlNode = new SplashUrlNode();
        $callback = new SplashRoute('foo/*', 'myController', 'myMethod', 'myTitle', 'fullComment', ['GET', 'POST']);
        $splashUrlNode->registerCallback($callback);
        $callback2 = new SplashRoute('foo/*', 'myController2', 'myMethod2', 'myTitle', 'fullComment', ['GET']);
        $this->expectException(SplashException::class);
        $splashUrlNode->registerCallback($callback2);
    }

    public function testDoubleParameter()
    {
        $splashUrlNode = new SplashUrlNode();
        $callback = new SplashRoute('foo/{var}/bar/{var}/', 'myController', 'myMethod', 'myTitle', 'myComment');
        $splashUrlNode->registerCallback($callback);

        $this->expectException(SplashException::class);
        $splashUrlNode->walk('foo/12/bar/42/', new ServerRequest([], [], '/toto', 'GET'));
    }

    public function testFallbackToWilcard()
    {
        $splashUrlNode = new SplashUrlNode();
        $callback = new SplashRoute('foo/bar/baz', 'myController', 'myMethod', 'myTitle', 'fullComment');
        $splashUrlNode->registerCallback($callback);
        $callback2 = new SplashRoute('foo/*', 'myController2', 'myMethod2', 'myTitle', 'fullComment');
        $splashUrlNode->registerCallback($callback2);

        $result = $splashUrlNode->walk('foo/bar', new ServerRequest([], [], '/foo/bar/biz', 'POST'));
        $this->assertEquals('myController2', $result->getControllerInstanceName());
        $this->assertEquals('myMethod2', $result->getMethodName());
    }
}
