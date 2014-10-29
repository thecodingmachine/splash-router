<?php
namespace Mouf\Mvc\Splash\Controllers;

use Mouf\Annotations\URLAnnotation;
use Mouf\Reflection\MoufReflectionMethod;

use Mouf\Mvc\Splash\Services\SplashRoute;

use Mouf\Mvc\Splash\Services\FilterUtils;

use Mouf\Mvc\Splash\Services\SplashUtils;

use Mouf\Mvc\Splash\Utils\ApplicationException;

use Mouf\Reflection\MoufReflectionClass;

use Mouf\MoufManager;

use Mouf\Mvc\Splash\Services\UrlProviderInterface;

use Mouf\Html\HtmlElement\Scopable;

/*require_once dirname(__FILE__)."/../views/404.php";
require_once dirname(__FILE__)."/../views/500.php";
require_once ROOT_PATH.'mouf/reflection/MoufReflectionClass.php';*/

abstract class Controller implements Scopable, UrlProviderInterface {
	
	/**
	 * Returns the default template used in Splash.
	 * This can be configured in the "splash" instance.
	 * Returns null if the "splash" instance does not exist.
	 *
	 * @return TemplateInterface
	 */
	public static function getTemplate() {
		if (MoufManager::getMoufManager()->instanceExists("splash")) {
			$template = MoufManager::getMoufManager()->getInstance("splash")->defaultTemplate;
			return $template;
		} else {
			return null;
		}
	}

	/**
	 * Inludes the file (useful to load a view inside the Controllers scope).
	 *
	 * @param unknown_type $file
	 */
	public function loadFile($file) {
		include $file;
	}
	
	/**
	 * Returns an instance of the logger used by default in Splash.
	 * This logger can be configured in the "splash" instance.
	 * Note: in Drusplash, there is no such "splash" instance. Therefore, null will be returned.
	 * 
	 * @return LogInterface
	 */
	/*public static function getLogger() {
		if (MoufManager::getMoufManager()->instanceExists("splash")) {
			return MoufManager::getMoufManager()->getInstance("splash")->log;
		} else {
			return null;
		}
	}*/
	
	/**
	 * Returns the list of URLs that can be accessed, and the function/method that should be called when the URL is called.
	 * 
	 * @return array<SplashRoute>
	 */
	public function getUrlsList() {		
		// Let's analyze the controller and get all the @Action annotations:
		$urlsList = array();
		$moufManager = MoufManager::getMoufManager();
		
		$refClass = new MoufReflectionClass(get_class($this));
        $instanceName = $moufManager->findInstanceName($this);
        $instance = $moufManager->getInstanceDescriptor($instanceName);
		
		foreach ($refClass->getMethods() as $refMethod) {
			/* @var $refMethod MoufReflectionMethod */
			$title = null;
			// Now, let's check the "Title" annotation (note: we do not support multiple title annotations for the same method)
			if ($refMethod->hasAnnotation('Title')) {
				$titles = $refMethod->getAnnotations('Title');
				if (count($titles)>1) {
					throw new Exception("Only one @Title annotation allowed per method.");
				}
				/* @var $titleAnnotation TitleAnnotation */
				$titleAnnotation = $titles[0];
				$title = $titleAnnotation->getTitle();
			}
			
			// First, let's check the "Action" annotation	
			if ($refMethod->hasAnnotation('Action')) {
				$methodName = $refMethod->getName(); 
				if ($methodName == "index" || $methodName == "defaultAction") {
					$url = $moufManager->findInstanceName($this)."/";
				} else {
					$url = $moufManager->findInstanceName($this)."/".$methodName;
				}
				$parameters = SplashUtils::mapParameters($refMethod);
				$filters = FilterUtils::getFilters($refMethod, $this);
				$urlsList[] = new SplashRoute($url, $moufManager->findInstanceName($this), $refMethod->getName(), $title, $refMethod->getDocCommentWithoutAnnotations(), $refMethod->getDocComment(), $this->getSupportedHttpMethods($refMethod), $parameters, $filters);
			}

			// Now, let's check the "URL" annotation (note: we support multiple URL annotations for the same method)
			if ($refMethod->hasAnnotation('URL')) {
				$urls = $refMethod->getAnnotations('URL');
				foreach ($urls as $urlAnnotation) {
					/* @var $urlAnnotation URLAnnotation */
					$url = $urlAnnotation->getUrl();

                    // Get public properties if they exist in the URL
                    if (preg_match_all('/([^\{$]*){\$this->([^\/]*)}([^\{$]*)/', $url, $output)) {
                        $url = $output[1][0];
                        foreach($output[2] as $key => $param){
                            $properties[$key] = $instance->getProperty($param)->getValue();
                        }
                        foreach($output[3] as $key => $path){
                            $property = $properties[$key];
                            $url .= $property.$path;
                        }
                    }

                    $newUrlAnnotation = new URLAnnotation($url);
					$url = ltrim($url, "/");
					$parameters = SplashUtils::mapParameters($refMethod, $newUrlAnnotation);
					$filters = FilterUtils::getFilters($refMethod, $this);
					$urlsList[] = new SplashRoute($url, $instanceName, $refMethod->getName(), $title, $refMethod->getDocCommentWithoutAnnotations(), $refMethod->getDocComment(), $this->getSupportedHttpMethods($refMethod), $parameters, $filters);
				}
			}
			
		}
		
		return $urlsList;
	}
	
	/**
	 * Returns the supported HTTP methods on this function, based on the annotations (@Get, @Post, etc...)
	 * @param MoufReflectionMethod $refMethod
	 */
	private function getSupportedHttpMethods(MoufReflectionMethod $refMethod) {
		$methods = array();
		if ($refMethod->hasAnnotation('Get')) {
			$methods[] = "GET";
		}
		if ($refMethod->hasAnnotation('Post')) {
			$methods[] = "POST";
		}
		if ($refMethod->hasAnnotation('Put')) {
			$methods[] = "PUT";
		}
		if ($refMethod->hasAnnotation('Delete')) {
			$methods[] = "DELETE";
		}
		return $methods;
	}
}
?>