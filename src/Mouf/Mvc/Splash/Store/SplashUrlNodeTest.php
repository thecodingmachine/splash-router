<?php
namespace tests\units;
require_once '../../../../../../mageekguy.atoum.phar';
require_once '../../../../../../Mouf.php';
require_once 'SplashUrlNode.php';
require_once '../../../../splash-common/3.3/services/SplashRoute.php';

use \mageekguy\atoum;

/**
 * A SplashUrlNode is a datastructure optimised to navigate all possible URLs known to the application. 
 * A SplashUrlNode represents all possible routes starting at the current position (just after a / in a URL)
 * 
 * @author David Negrier
 */
class SplashUrlNode extends atoum\test {
	public function testAddUrl()
    {
        $splashUrlNode = new \SplashUrlNode();
        $callback = new \SplashRoute("toto/tata", "myController", "myMethod", "myTitle", "myComment", "fullComment", array("GET", "POST"));
        $splashUrlNode->registerCallback($callback);
        
        $result = $splashUrlNode->walk("toto/tata", "GET");
        /* @var $result SplashRoute */
        $this->assert->object($result)->isInstanceOf("SplashRoute");
        $this->assert->string($result->controllerInstanceName)->isEqualTo('myController');
        $this->assert->string($result->methodName)->isEqualTo('myMethod');
    }
    
    public function testTrailingSlashUrl()
    {
    	$splashUrlNode = new \SplashUrlNode();
    	$callback = new \SplashRoute("toto/tata/", "myController", "myMethod", "myTitle", "myComment", "fullComment", array("GET", "POST"));
    	$splashUrlNode->registerCallback($callback);
    
    	$result = $splashUrlNode->walk("toto/tata/", "GET");
    	/* @var $result SplashRoute */
    	$this->assert->object($result)->isInstanceOf("SplashRoute");
    	$this->assert->string($result->controllerInstanceName)->isEqualTo('myController');
    	$this->assert->string($result->methodName)->isEqualTo('myMethod');
    }

    public function testRootUrl()
    {
    	$splashUrlNode = new \SplashUrlNode();
    	$callback = new \SplashRoute("/", "myController", "myMethod", "myTitle", "myComment", "fullComment", array("GET", "POST"));
    	$splashUrlNode->registerCallback($callback);
    
    	$result = $splashUrlNode->walk("/", "GET");
    	/* @var $result SplashRoute */
    	$this->assert->object($result)->isInstanceOf("SplashRoute");
    	$this->assert->string($result->controllerInstanceName)->isEqualTo('myController');
    	$this->assert->string($result->methodName)->isEqualTo('myMethod');
    }

    /**
     * 
     * Enter description here ...
     */
    public function testSameUrls()
    {
    	
    	$this->assert
    	->exception(function() {
	    	$splashUrlNode = new \SplashUrlNode();
	    	$callback = new \SplashRoute("/", "myController", "myMethod", "myTitle", "myComment", "fullComment", array("GET", "POST"));
	    	$splashUrlNode->registerCallback($callback);
	    	$callback = new \SplashRoute("/", "myController", "myMethod", "myTitle", "myComment", "fullComment", array("GET", "POST"));
	    	$splashUrlNode->registerCallback($callback);
       	})
    	->isInstanceOf('SplashException');
    	
    }
    
    /**
     *
     */
    public function testGlobalUrlCatchGet()
    {
    	 
    	$splashUrlNode = new \SplashUrlNode();
    	$callback = new \SplashRoute("/toto", "myController", "myMethod", "myTitle", "myComment", "fullComment", array());
    	$splashUrlNode->registerCallback($callback);
    
    	$result = $splashUrlNode->walk("/toto", "GET");
    	/* @var $result SplashRoute */
    	$this->assert->object($result)->isInstanceOf("SplashRoute");
    	$this->assert->string($result->controllerInstanceName)->isEqualTo('myController');
    	$this->assert->string($result->methodName)->isEqualTo('myMethod');
    	    	 
    }

    /**
     *
     */
    public function testMultiUrls()
    {
    
    	$splashUrlNode = new \SplashUrlNode();
    	$callback = new \SplashRoute("/toto", "myControllerOk", "myMethodOk", "myTitle", "myComment", "fullComment", array());
    	$splashUrlNode->registerCallback($callback);
    	$callback = new \SplashRoute("/toto/tata", "myController", "myMethod", "myTitle", "myComment", "fullComment", array());
    	$splashUrlNode->registerCallback($callback);
    	$callback = new \SplashRoute("/tata", "myController", "myMethod", "myTitle", "myComment", "fullComment", array());
    	$splashUrlNode->registerCallback($callback);
    	 
    	$result = $splashUrlNode->walk("/toto", "POST");
    	/* @var $result SplashRoute */
    	$this->assert->object($result)->isInstanceOf("SplashRoute");
    	$this->assert->string($result->controllerInstanceName)->isEqualTo('myControllerOk');
    	$this->assert->string($result->methodName)->isEqualTo('myMethodOk');
    	 
    }
    
    /**
    *
    */
    public function testParametersUrls()
    {
    
    	$splashUrlNode = new \SplashUrlNode();
    	$callback = new \SplashRoute("/toto/{var}/tata", "myController", "myMethod", "myTitle", "myComment", "fullComment", array());
    	$splashUrlNode->registerCallback($callback);
    
    	$result = $splashUrlNode->walk("/toto/12/tata", "POST");
    	/* @var $result SplashRoute */
    	$this->assert->object($result)->isInstanceOf("SplashRoute");
    	$this->assert->string($result->controllerInstanceName)->isEqualTo('myControllerOk');
    	$this->assert->string($result->methodName)->isEqualTo('myMethodOk');
    	$this->assert->isTrue($result->parameters[0])->isEqualTo(12);    
    }

    /**
     *
     */
    public function testWildcardUrls()
    {
    
    	$splashUrlNode = new \SplashUrlNode();
    	$callback = new \SplashRoute("/toto/*", "myController", "myMethod", "myTitle", "myComment", "fullComment", array());
    	$splashUrlNode->registerCallback($callback);
    
    	$result = $splashUrlNode->walk("/toto/tata/titi", "POST");
    	/* @var $result SplashRoute */
    	$this->assert->object($result)->isInstanceOf("SplashRoute");
    	$this->assert->string($result->controllerInstanceName)->isEqualTo('myControllerOk');
    	$this->assert->string($result->methodName)->isEqualTo('myMethodOk');
    	$this->assert->isTrue($result->parameters[0])->isEqualTo(12);
    	
    	$result = $splashUrlNode->walk("/toto/", "POST");
    	/* @var $result SplashRoute */
    	$this->assert->object($result)->isInstanceOf("SplashRoute");
    	$this->assert->string($result->controllerInstanceName)->isEqualTo('myControllerOk');
    	$this->assert->string($result->methodName)->isEqualTo('myMethodOk');
    	$this->assert->isTrue($result->parameters[0])->isEqualTo(12);
    	 
    }
    
}