<?php


namespace Mouf\Mvc\Splash\Services;

use Interop\Container\ContainerInterface;
use Mouf\Annotations\URLAnnotation;
use Mouf\MoufManager;
use Mouf\Mvc\Splash\Utils\SplashException;
use Mouf\Reflection\MoufReflectionClass;
use Mouf\Reflection\MoufReflectionMethod;
use ReflectionMethod;

/**
 * This class is in charge of registering controller's routes.
 */
class ControllerRegistry implements UrlProviderInterface
{
    private $container;

    private $controllers;

    /**
     * @var ParameterFetcherRegistry
     */
    private $parameterFetcherRegistry;

    /**
     * Initializes the registry with an array of container instances names.
     *
     * @param ContainerInterface $container The container to fetch controllers from
     * @param ParameterFetcherRegistry $parameterFetcherRegistry
     * @param string[] $controllers An array of controller instance name (as declared in the container)
     */
    public function __construct(ContainerInterface $container, ParameterFetcherRegistry $parameterFetcherRegistry, array $controllers = [])
    {
        $this->container = $container;
        $this->controllers = $controllers;
        $this->parameterFetcherRegistry = $parameterFetcherRegistry;
    }


    /**
     * Adds a container to the registry (by its instance name).
     * Note: any object that has a @Action or @URL annotation is a controller.
     *
     * @param string $controller
     * @return ControllerRegistry
     */
    public function addController(string $controller) : ControllerRegistry
    {
        $this->controllers[] = $controller;
        return $this;
    }

    /**
     * Returns the list of URLs that can be accessed, and the function/method that should be called when the URL is called.
     *
     * @param string $instanceName The identifier for this object in the container.
     * @return SplashRoute[]
     */
    public function getUrlsList($instanceName)
    {
        // FIXME: $instanceName is no more needed! o_O
        $urlsList = [];

        foreach ($this->controllers as $controllerInstanceName) {
            $urlsList = array_merge($urlsList, $this->analyzeController($controllerInstanceName));
        }

        return $urlsList;
    }

    private function analyzeController(string $controllerInstanceName)
    {
        // Let's analyze the controller and get all the @Action annotations:
        $urlsList = array();

        $controller = $this->container->get($controllerInstanceName);

        $refClass = new MoufReflectionClass(get_class($controller));

        foreach ($refClass->getMethods() as $refMethod) {
            /* @var $refMethod MoufReflectionMethod */
            $title = null;
            // Now, let's check the "Title" annotation (note: we do not support multiple title annotations for the same method)
            if ($refMethod->hasAnnotation('Title')) {
                $titles = $refMethod->getAnnotations('Title');
                if (count($titles) > 1) {
                    throw new SplashException('Only one @Title annotation allowed per method.');
                }
                /* @var $titleAnnotation TitleAnnotation */
                $titleAnnotation = $titles[0];
                $title = $titleAnnotation->getTitle();
            }

            // First, let's check the "Action" annotation
            if ($refMethod->hasAnnotation('Action')) {
                $methodName = $refMethod->getName();
                if ($methodName === 'index' || $methodName === 'defaultAction') {
                    $url = $controllerInstanceName.'/';
                } else {
                    $url = $controllerInstanceName.'/'.$methodName;
                }
                $parameters = $this->parameterFetcherRegistry->mapParameters($refMethod);
                $filters = FilterUtils::getFilters($refMethod, $controller);
                $urlsList[] = new SplashRoute($url, $controllerInstanceName, $refMethod->getName(), $title, $refMethod->getDocCommentWithoutAnnotations(), $refMethod->getDocComment(), $this->getSupportedHttpMethods($refMethod), $parameters, $filters);
            }

            // Now, let's check the "URL" annotation (note: we support multiple URL annotations for the same method)
            if ($refMethod->hasAnnotation('URL')) {
                $urls = $refMethod->getAnnotations('URL');
                foreach ($urls as $urlAnnotation) {
                    /* @var $urlAnnotation URLAnnotation */
                    $url = $urlAnnotation->getUrl();

                    // Get public properties if they exist in the URL
                    //if (preg_match_all('/([^\{]*){\$this->([^\/]*)}([^\{]*)/', $url, $output)) {
                    if (preg_match_all('/[^{]*{\$this->([^\/]*)}[^{]*/', $url, $output)) {
                        foreach ($output[1] as $param) {
                            $value = $this->readPrivateProperty($controller, $param);
                            $url = str_replace('{$this->'.$param.'}', $value, $url);
                        }
                    }

                    $newUrlAnnotation = new URLAnnotation($url);
                    $url = ltrim($url, '/');
                    $parameters = $this->parameterFetcherRegistry->mapParameters($refMethod, $newUrlAnnotation->getUrl());
                    $filters = FilterUtils::getFilters($refMethod, $controller);
                    $urlsList[] = new SplashRoute($url, $controllerInstanceName, $refMethod->getName(), $title, $refMethod->getDocCommentWithoutAnnotations(), $refMethod->getDocComment(), $this->getSupportedHttpMethods($refMethod), $parameters, $filters);
                }
            }
        }

        return $urlsList;
    }

    /**
     * Reads a private property value.
     * Credit to Ocramius: https://ocramius.github.io/blog/accessing-private-php-class-members-without-reflection/
     *
     * @param object $object
     * @param string $property
     * @return mixed
     */
    private function &readPrivateProperty($object, string $property)
    {
        $value = & \Closure::bind(function & () use ($property) {
            return $this->$property;
        }, $object, $object)->__invoke();

        return $value;
    }

    /**
     * Returns the supported HTTP methods on this function, based on the annotations (@Get, @Post, etc...).
     *
     * @param MoufReflectionMethod $refMethod
     * @return array
     */
    private function getSupportedHttpMethods(MoufReflectionMethod $refMethod) : array
    {
        $methods = array();
        if ($refMethod->hasAnnotation('Get')) {
            $methods[] = 'GET';
        }
        if ($refMethod->hasAnnotation('Post')) {
            $methods[] = 'POST';
        }
        if ($refMethod->hasAnnotation('Put')) {
            $methods[] = 'PUT';
        }
        if ($refMethod->hasAnnotation('Delete')) {
            $methods[] = 'DELETE';
        }

        return $methods;
    }

    /**
     * Returns an array of parameters present in the URL.
     * If the URL is /user/{id}/login/{name}, the returns array will be:
     *  [ "id"=>"id",
     *    "name"=>"name" ]
     *
     * @param URLAnnotation $urlAnnotation
     * @return array
     */
    /*private static function getUrlParameters(URLAnnotation $urlAnnotation) : array
    {
        $urlParamsList = [];
        $url = $urlAnnotation->getUrl();
        $urlParts = explode('/', $url);
        foreach ($urlParts as $part) {
            if (strpos($part, '{') === 0 && strpos($part, '}') === strlen($part) - 1) {
                // Parameterized URL element
                $varName = substr($part, 1, strlen($part) - 2);
                $urlParamsList[$varName] = $varName;
            }
        }
        return $urlParamsList;
    }*/
}