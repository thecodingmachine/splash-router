<?php

namespace Mouf\Mvc\Splash\Services;

use Doctrine\Common\Annotations\Reader;
use Interop\Container\ContainerInterface;
use Mouf\Mvc\Splash\Annotations\Action;
use Mouf\Mvc\Splash\Annotations\Delete;
use Mouf\Mvc\Splash\Annotations\Get;
use Mouf\Mvc\Splash\Annotations\Post;
use Mouf\Mvc\Splash\Annotations\Put;
use Mouf\Mvc\Splash\Annotations\Title;
use Mouf\Mvc\Splash\Annotations\URL;
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
     * @var Reader
     */
    private $annotationReader;

    /**
     * Initializes the registry with an array of container instances names.
     *
     * @param ContainerInterface       $container                The container to fetch controllers from
     * @param ParameterFetcherRegistry $parameterFetcherRegistry
     * @param Reader                   $annotationReader         A Doctrine annotation reader
     * @param string[]                 $controllers              An array of controller instance name (as declared in the container)
     */
    public function __construct(ContainerInterface $container, ParameterFetcherRegistry $parameterFetcherRegistry, Reader $annotationReader, array $controllers = [])
    {
        $this->container = $container;
        $controllersArr = array_values($controllers);
        $this->controllers = array_combine($controllersArr, $controllersArr);
        $this->parameterFetcherRegistry = $parameterFetcherRegistry;
        $this->annotationReader = $annotationReader;
    }

    /**
     * Adds a container to the registry (by its instance name).
     * Note: any object that has a @Action or @URL annotation is a controller.
     *
     * @param string $controller
     *
     * @return ControllerRegistry
     */
    public function addController(string $controller) : ControllerRegistry
    {
        $this->controllers[$controller] = $controller;

        return $this;
    }

    /**
     * Returns the list of URLs that can be accessed, and the function/method that should be called when the URL is called.
     *
     * @param string $instanceName The identifier for this object in the container.
     *
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

        $refClass = new \ReflectionClass($controller);

        foreach ($refClass->getMethods() as $refMethod) {
            /* @var $refMethod MoufReflectionMethod */
            $title = null;
            // Now, let's check the "Title" annotation (note: we do not support multiple title annotations for the same method)
            $titleAnnotation = $this->annotationReader->getMethodAnnotation($refMethod, Title::class);
            if ($titleAnnotation !== null) {
                /* @var $titleAnnotation TitleAnnotation */
                $title = $titleAnnotation->getTitle();
            }

            // First, let's check the "Action" annotation
            $actionAnnotation = $this->annotationReader->getMethodAnnotation($refMethod, Action::class);
            if ($actionAnnotation !== null) {
                $methodName = $refMethod->getName();
                if ($methodName === 'index') {
                    $url = $controllerInstanceName.'/';
                } else {
                    $url = $controllerInstanceName.'/'.$methodName;
                }
                $parameters = $this->parameterFetcherRegistry->mapParameters($refMethod);
                $filters = FilterUtils::getFilters($refMethod, $this->annotationReader);
                $urlsList[] = new SplashRoute($url, $controllerInstanceName, $refMethod->getName(), $title, $refMethod->getDocComment(), $this->getSupportedHttpMethods($refMethod), $parameters, $filters);
            }

            // Now, let's check the "URL" annotation (note: we support multiple URL annotations for the same method)
            $annotations = $this->annotationReader->getMethodAnnotations($refMethod);

            foreach ($annotations as $annotation) {
                if (!$annotation instanceof URL) {
                    continue;
                }

                /* @var $annotation URL */
                $url = $annotation->getUrl();

                // Get public properties if they exist in the URL
                if (preg_match_all('/[^{]*{\$this->([^\/]*)}[^{]*/', $url, $output)) {
                    foreach ($output[1] as $param) {
                        $value = $this->readPrivateProperty($controller, $param);
                        $url = str_replace('{$this->'.$param.'}', $value, $url);
                    }
                }

                $url = ltrim($url, '/');
                $parameters = $this->parameterFetcherRegistry->mapParameters($refMethod, $url);
                $filters = FilterUtils::getFilters($refMethod, $this->annotationReader);
                $urlsList[] = new SplashRoute($url, $controllerInstanceName, $refMethod->getName(), $title, $refMethod->getDocComment(), $this->getSupportedHttpMethods($refMethod), $parameters, $filters);
            }
        }

        return $urlsList;
    }

    /**
     * Reads a private property value.
     * Credit to Ocramius: https://ocramius.github.io/blog/accessing-private-php-class-members-without-reflection/.
     *
     * @param object $object
     * @param string $property
     *
     * @return mixed
     */
    private function &readPrivateProperty($object, string $property)
    {
        $value = &\Closure::bind(function &() use ($property) {
            return $this->$property;
        }, $object, $object)->__invoke();

        return $value;
    }

    /**
     * Returns the supported HTTP methods on this function, based on the annotations (@Get, @Post, etc...).
     *
     * @param ReflectionMethod $refMethod
     *
     * @return array
     */
    private function getSupportedHttpMethods(ReflectionMethod $refMethod) : array
    {
        $methods = array();

        if ($this->annotationReader->getMethodAnnotation($refMethod, Get::class)) {
            $methods[] = 'GET';
        }
        if ($this->annotationReader->getMethodAnnotation($refMethod, Post::class)) {
            $methods[] = 'POST';
        }
        if ($this->annotationReader->getMethodAnnotation($refMethod, Put::class)) {
            $methods[] = 'PUT';
        }
        if ($this->annotationReader->getMethodAnnotation($refMethod, Delete::class)) {
            $methods[] = 'DELETE';
        }

        return $methods;
    }

    /**
     * Returns a unique tag representing the list of SplashRoutes returned.
     * If the tag changes, the cache is flushed by Splash.
     *
     * Important! This must be quick to compute.
     *
     * @return mixed
     */
    public function getExpirationTag() : string
    {
        // An approximate, quick-to-compute rule that will force renewing the cache if a controller is added are a parameter is fetched.
        return implode('-/-', $this->controllers).'-'.count($this->parameterFetcherRegistry);
    }
}
