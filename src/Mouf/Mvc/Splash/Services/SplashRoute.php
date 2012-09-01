<?php
namespace Mouf\Mvc\Splash\Services;

/**
 * A callback used to access a page.
 * 
 * @author David
 */
class SplashRoute {
	
	public $url;
	
	/**
	 * List of HTTP methods allowed for this callback.
	 * If empty, all methods are allowed.
	 * @var array<string>
	 */
	public $httpMethods;
	
	public $controllerInstanceName;
	
	public $methodName;
	
	public $title;
	
	public $comment;
	
	public $fullComment;
	
	/**
	 * An ordered list of parameters.
	 * The first parameter to be passed to the method will be fetched from values set $parameters[0], etc...
	 * 
	 * 
	 * @var array<SplashParameterFetcherInterface>
	 */
	public $parameters;
	
	/**
	 * A list of all filters to apply to the route.
	 * 
	 * @var array<AbstractFilter>
	 */
	public $filters;
	
	/**
	 * The list of parameters matched during the route.
	 * This is filled at runtime, by the SplashUrlNode class.
	 * 
	 * @var array<string, string>
	 */
	public $filledParameters = array();
	// Question: abstraire SplashRoute et rajouter un getCallbackHandler???
	
	public function __construct($url, $controllerInstanceName, $methodName, $title, $comment, $fullComment = null, $httpMethods = array(), $parameters = array(), $filters=array()) {
		$this->url = $url;
		$this->httpMethods = $httpMethods;
		$this->controllerInstanceName = $controllerInstanceName;
		$this->methodName = $methodName;
		$this->title = $title;
		$this->comment = $comment;
		$this->fullComment = $fullComment;
		$this->parameters = $parameters;
		$this->filters = $filters;
	}
}

?>