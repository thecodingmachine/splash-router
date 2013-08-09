<?php
namespace Mouf\Mvc\Splash;

use Mouf\Mvc\Splash\Controllers\Controller;

use Mouf\Utils\Action\ActionInterface;

use Mouf\MoufManager;

use Mouf\Mvc\Splash\Services\SplashRoute;

use Mouf\Mvc\Splash\Services\UrlProviderInterface;
use Mouf\Utils\Common\UrlInterface;


/**
 * This class represents a single URL that can be bound to a specific behaviour.
 * 
 * FIXME: we should not have to extend Controller. Modify Splash to remove this limitation.
 * 
 * @author David Negrier
 */
class UrlEntryPoint extends Controller implements UrlProviderInterface, UrlInterface {
	
	private $url;
	private $actions;
	
	/**
	 * Construct the object passing in parameter the URL and the list of actions to perform
	 * 
	 * @param string $url The URL to bind to. It should
	 * @param array<ActionInterface> $actions
	 */
	public function __construct($url, array $actions = array()) {
		$this->url = $url;
		$this->actions = $actions;
	}
	
	/**
	 * Method called when the URL is called.
	 */
	public function action() {
		foreach ($this->actions as $action) {
			$action->run();
		}
	}
	
	/**
	 * Returns the list of URLs that can be accessed, and the function/method that should be called when the URL is called.
	 *
	 * @return array<SplashRoute>
	 */
	function getUrlsList() {
		$instanceName = MoufManager::getMoufManager()->findInstanceName($this);
		
		$route = new SplashRoute($this->url, $instanceName, "action", null, null);
		return array($route);
	}
	
	/* (non-PHPdoc)
	 * @see \Mouf\Utils\Common\UrlInterface::getUrl()
	 */
	public function getUrl() {
		return $this->url;
	}

}