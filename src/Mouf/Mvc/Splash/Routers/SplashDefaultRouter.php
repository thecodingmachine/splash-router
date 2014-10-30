<?php
namespace Mouf\Mvc\Splash\Routers;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Mouf\Utils\Cache\CacheInterface;
use Mouf\MoufManager;
use Mouf\Mvc\Splash\Store\SplashUrlNode;
use Psr\Log\LoggerInterface;
use Mouf\Mvc\Splash\Controllers\WebServiceInterface;
use Mouf\Mvc\Splash\Services\SplashRequestContext;
use Mouf\Mvc\Splash\Services\SplashUtils;

class SplashDefaultRouter implements HttpKernelInterface {
	
	/**
	 * The logger used by Splash
	 *
	 * @var LoggerInterface
	 */
	private $log;
	
	/**
	 * Splash uses the cache service to store the URL mapping (the mapping between a URL and its controller/action)
	 *
	 * @var CacheInterface
	 */
	private $cacheService;

	/**
	 * The router that will handle the request if this one fails to find a matching route
	 * 
	 * @var HttpKernelInterface
	 */
	private $fallBackRouter;
	
	/**
	 * @Important
	 * @param HttpKernelInterface $fallBackRouter Router used if no page is found for this controller.
	 * @param CacheInterface $cacheService Splash uses the cache service to store the URL mapping (the mapping between a URL and its controller/action)
	 * @param LoggerInterface $log The logger used by Splash
	 */
	public function __construct(HttpKernelInterface $fallBackRouter, CacheInterface $cacheService = null, LoggerInterface $log = null){
		$this->fallBackRouter = $fallBackRouter;
		$this->cacheService = $cacheService;
		$this->log = $log;
	}
	
	/**
	 * Handles a Request to convert it to a Response.
	 *
	 * When $catch is true, the implementation must catch all exceptions
	 * and do its best to convert them to a Response instance.
	 *
	 * @param Request $request A Request instance
	 * @param int     $type    The type of the request
	 *                          (one of HttpKernelInterface::MASTER_REQUEST or HttpKernelInterface::SUB_REQUEST)
	 * @param bool    $catch Whether to catch exceptions or not
	 *
	 * @return Response A Response instance
	 *
	 * @throws \Exception When an Exception occurs during processing
	 */
	public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true){
		// FIXME: find a better way?
		$splashUrlPrefix = ROOT_URL; 
		
		if ($this->cacheService == null) {
			// Retrieve the split parts
			$urlsList = $this->getSplashActionsList();
			$urlNodes = $this->generateUrlNode($urlsList);
		} else {
			$urlNodes = $this->cacheService->get("splashUrlNodes");
			if ($urlNodes == null) {
				// No value in cache, let's get the URL nodes
				$urlsList = $this->getSplashActionsList();
				$urlNodes = $this->generateUrlNode($urlsList);
				$this->cacheService->set("splashUrlNodes", $urlNodes);
			}
		}
			
		// TODO: add support for %instance% for injecting the instancename of the controller
			
		$request_array = parse_url($_SERVER['REQUEST_URI']);
			
		if ($request_array === false) {
			throw new SplashException("Malformed URL: ".$_SERVER['REQUEST_URI']);
		}
			
		$request_path = $request_array['path'];
		$httpMethod = $_SERVER['REQUEST_METHOD'];
	
		$pos = strpos($request_path, $splashUrlPrefix);
		if ($pos === FALSE) {
			throw new \Exception('Error: the prefix of the web application "'.$splashUrlPrefix.'" was not found in the URL. The application must be misconfigured. Check the ROOT_URL parameter in your config.php file at the root of your project. It should have the same value as the RewriteBase parameter in your .htaccess file.');
		}
	
		$tailing_url = substr($request_path, $pos+strlen($splashUrlPrefix));
	
		$context = new SplashRequestContext();
		$splashRoute = $urlNodes->walk($tailing_url, $httpMethod);
	
		if ($splashRoute === null){
			return $this->fallBackRouter->handle($request, $type, $catch);
		}
			
		$controller = MoufManager::getMoufManager()->getInstance($splashRoute->controllerInstanceName);
		$action = $splashRoute->methodName;
		
		$context->setUrlParameters($splashRoute->filledParameters);
			
	
		if ($this->log != null) {
			$this->log->info("Routing user with URL {url} to controller {controller} and action {action}", array(
				'url' => $_SERVER['REDIRECT_URL'],
				'controller' => get_class($controller),
				'action' => $action
			));
		}
	
		if ($controller instanceof WebServiceInterface) {
			// FIXME: handle correctly webservices (or remove this exception and handle
			// webservice the way we handle controllers
			$response = SplashUtils::buildControllerResponse(
				function() use ($controller){
					$this->handleWebservice($controller);
				}
			);
			return $response;
		} else {
			// Let's pass everything to the controller:
			$args = array();
			foreach ($splashRoute->parameters as $paramFetcher) {
				/* @var $param SplashParameterFetcherInterface */
				try {
					$args[] = $paramFetcher->fetchValue($context);
				} catch (SplashValidationException $e) {
	
					$e->setPrependedMessage(SplashUtils::translate("validate.error.while.validating.parameter", $paramFetcher->getName()));
					throw $e;
				}
			}
	
			// Handle action__GET or action__POST method (for legacy code).
			if(method_exists($controller, $action.'__'.$_SERVER['REQUEST_METHOD'])) {
				$action = $action.'__'.$_SERVER['REQUEST_METHOD'];
			}
	
			$filters = $splashRoute->filters;
	
			// Apply filters
			for ($i=count($filters)-1; $i>=0; $i--) {
				$filters[$i]->beforeAction();
			}
				
			$response = SplashUtils::buildControllerResponse(
				function() use ($controller, $action, $args){
					call_user_func_array(array($controller,$action), $args);
				}
			);
				
			foreach ($filters as $filter) {
				$filter->afterAction();
			}
			
			return $response;
		}
	}
	
	/**
	 * Handles the call to the webservice
	 *
	 * @param WebServiceInterface $webserviceInstance
	 */
	private function handleWebservice(WebServiceInterface $webserviceInstance) {
		$url = $webserviceInstance->getWebserviceUri();
	
		$server = new SoapServer(null, array('uri' => $url));
		$server->setObject($webserviceInstance);
		$server->handle();
	}
	
	/**
	 * Returns the list of all SplashActions.
	 * This call is LONG and should be cached
	 *
	 * @return array<SplashAction>
	 */
	private function getSplashActionsList() {
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
	
	/**
	 * Generates the URLNodes from the list of URLS.
	 * URLNodes are a very efficient way to know whether we can access our page or not.
	 *
	 * @param array<SplashAction> $urlsList
	 * @return SplashUrlNode
	 */
	private function generateUrlNode($urlsList) {
		$urlNode = new SplashUrlNode();
		foreach ($urlsList as $splashAction) {
			$urlNode->registerCallback($splashAction);
		}
		return $urlNode;
	}
	
	/**
	 * Purges the urls cache.
	 * @throws Exception
	 */
	public function purgeUrlsCache() {
		$this->cacheService->purge("splashUrlNodes");
	}
	
}