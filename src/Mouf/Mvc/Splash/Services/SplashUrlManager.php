<?php
namespace Mouf\Mvc\Splash\Services;

use Mouf\Reflection\MoufReflectionProxy;
use Mouf\ClassProxy;
use Mouf\MoufManager;

/**
 * This class is in charge of retrieving the URLs that can be accessed.
 *  
 * @author David
 */
class SplashUrlManager {
	
	/**
	 * Returns the list of URLs that can be accessed, and the function/method that should be called when the URL is called.
	 * 
	 * @return array<SplashRoute>
	 */
	public static function getUrlsList($selfedit) {
		
		return self::getUrlsByProxy($selfedit);
	}
	
	public static function getUrlsByProxy($selfEdit) {
		// Let's perform a late loading on the SplashRoute class (because the admin version of Mouf might use a different version of the class than the application
		// itself, we cannot include this file directly, since it is used inside the admin of mouf).
		
		// TODO: the proxy should return JSON instead of objects (because Splash is used both on the admin and on the app side, with different versions)
		//require_once dirname(__FILE__)."/SplashRoute.php";
		
		$class = new ClassProxy('Mouf\\Mvc\\Splash\\Services\\SplashUrlManager', $selfEdit == 'true');
		return $class->getUrlsDirect();
	}
	
	/**
	 * Returns the list of URLs. This function must be called in the context of the application
	 * (so using the getUrlsByProxy method).
	 */
	public static function getUrlsDirect() {
		$moufManager = MoufManager::getMoufManager();
		$instanceNames = $moufManager->findInstances("Mouf\\Mvc\\Splash\\Services\\UrlProviderInterface");
		
		$urls = array();	
		
		foreach ($instanceNames as $instanceName) {
			$urlProvider = $moufManager->getInstance($instanceName);
			/* @var $urlProvider UrlProviderInterface */
			$tmpUrlList = $urlProvider->getUrlsList();
			$urls = array_merge($urls, $tmpUrlList);
		}
		return $urls;
	}
	
}


?>