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
        $callback = new SplashRoute('toto/tata', 'myController', 'myMethod', 'myTitle', 'myComment', 'fullComment', array('GET', 'POST'));
        $splashUrlNode->registerCallback($callback);

        $result = $splashUrlNode->walk('toto/tata', new ServerRequest([], [], 'toto/tata', 'GET'));

        /* @var $result SplashRoute */
        $this->assertInstanceOf(SplashRoute::class, $result);
        $this->assertEquals('myController', $result->controllerInstanceName);
        $this->assertEquals('myMethod', $result->methodName);
    }

    public function testTrailingSlashUrl()
    {
        $splashUrlNode = new SplashUrlNode();
        $callback = new SplashRoute('toto/tata/', 'myController', 'myMethod', 'myTitle', 'myComment', 'fullComment', array('GET', 'POST'));
        $splashUrlNode->registerCallback($callback);

        $result = $splashUrlNode->walk('toto/tata/', new ServerRequest([], [], 'toto/tata', 'GET'));
        /* @var $result SplashRoute */
        $this->assertInstanceOf(SplashRoute::class, $result);
        $this->assertEquals('myController', $result->controllerInstanceName);
        $this->assertEquals('myMethod', $result->methodName);
    }

    public function testRootUrl()
    {
        $splashUrlNode = new SplashUrlNode();
        $callback = new SplashRoute('/', 'myController', 'myMethod', 'myTitle', 'myComment', 'fullComment', array('GET', 'POST'));
        $splashUrlNode->registerCallback($callback);

        $result = $splashUrlNode->walk('/', new ServerRequest([], [], '/', 'GET'));
        /* @var $result SplashRoute */
        $this->assertInstanceOf(SplashRoute::class, $result);
        $this->assertEquals('myController', $result->controllerInstanceName);
        $this->assertEquals('myMethod', $result->methodName);
    }

    public function testSameUrls()
    {
        $splashUrlNode = new SplashUrlNode();
        $callback = new SplashRoute('/', 'myController', 'myMethod', 'myTitle', 'myComment', 'fullComment', array('GET', 'POST'));
        $splashUrlNode->registerCallback($callback);
        $callback = new SplashRoute('/', 'myController', 'myMethod', 'myTitle', 'myComment', 'fullComment', array('GET', 'POST'));

        $this->expectException(SplashException::class);
        $splashUrlNode->registerCallback($callback);
    }

    /**
     *
     */
    public function testGlobalUrlCatchGet()
    {
        $splashUrlNode = new SplashUrlNode();
        $callback = new SplashRoute('/toto', 'myController', 'myMethod', 'myTitle', 'myComment', 'fullComment', array());
        $splashUrlNode->registerCallback($callback);

        $result = $splashUrlNode->walk('/toto', new ServerRequest([], [], '/toto', 'GET'));
        /* @var $result SplashRoute */
        $this->assertInstanceOf(SplashRoute::class, $result);
        $this->assertEquals('myController', $result->controllerInstanceName);
        $this->assertEquals('myMethod', $result->methodName);
    }

    /**
     *
     */
    public function testMultiUrls()
    {
        $splashUrlNode = new SplashUrlNode();
        $callback = new SplashRoute('/toto', 'myControllerOk', 'myMethodOk', 'myTitle', 'myComment', 'fullComment', array());
        $splashUrlNode->registerCallback($callback);
        $callback = new SplashRoute('/toto/tata', 'myController', 'myMethod', 'myTitle', 'myComment', 'fullComment', array());
        $splashUrlNode->registerCallback($callback);
        $callback = new SplashRoute('/tata', 'myController', 'myMethod', 'myTitle', 'myComment', 'fullComment', array());
        $splashUrlNode->registerCallback($callback);

        $result = $splashUrlNode->walk('/toto', new ServerRequest([], [], '/toto', 'POST'));
        /* @var $result SplashRoute */
        $this->assertInstanceOf(SplashRoute::class, $result);
        $this->assertEquals('myControllerOk', $result->controllerInstanceName);
        $this->assertEquals('myMethodOk', $result->methodName);

    }

    /**
     *
     */
    public function testParametersUrls()
    {
        $splashUrlNode = new SplashUrlNode();
        $callback = new SplashRoute('/toto/{var}/tata', 'myController', 'myMethod', 'myTitle', 'myComment', 'fullComment', []);
        $splashUrlNode->registerCallback($callback);

        $result = $splashUrlNode->walk('/toto/12/tata', new ServerRequest([], [], '/toto/12/tata', 'POST'));
        /* @var $result SplashRoute */
        $this->assertInstanceOf(SplashRoute::class, $result);
        $this->assertEquals('myController', $result->controllerInstanceName);
        $this->assertEquals('myMethod', $result->methodName);
        $this->assertEquals(12, $result->parameters[0]);
    }

    /**
     *
     */
    public function testWildcardUrls()
    {
        $splashUrlNode = new SplashUrlNode();
        $callback = new SplashRoute('/toto/*', 'myController', 'myMethod', 'myTitle', 'myComment', 'fullComment', array());
        $splashUrlNode->registerCallback($callback);

        $result = $splashUrlNode->walk('/toto/tata/titi', new ServerRequest([], [], '/toto', 'POST'));
        /* @var $result SplashRoute */
        $this->assertInstanceOf(SplashRoute::class, $result);
        $this->assertEquals('myController', $result->controllerInstanceName);
        $this->assertEquals('myMethod', $result->methodName);

        $result = $splashUrlNode->walk('/toto/', new ServerRequest([], [], '/toto', 'POST'));
        /* @var $result SplashRoute */
        $this->assertInstanceOf(SplashRoute::class, $result);
        $this->assertEquals('myController', $result->controllerInstanceName);
        $this->assertEquals('myMethod', $result->methodName);
    }
}
