<?php
namespace Mouf\Mvc\Splash\Services;

/**
 * This interface is implemented by any Mouf Instance that want to open URL accesses to your application. 
 * 
 * @author David
 */
interface UrlProviderInterface {
	
	/**
	 * Returns the list of URLs that can be accessed, and the function/method that should be called when the URL is called.
	 * 
	 * @return array<SplashRoute>
	 */
	function getUrlsList();
}

?>