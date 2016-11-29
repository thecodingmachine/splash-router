<?php

namespace Mouf\Mvc\Splash\Services;

use Doctrine\Common\Annotations\Reader;
use Interop\Container\ContainerInterface;
use Mouf\Mvc\Splash\Annotations\Action;
use Mouf\Mvc\Splash\Annotations\URL;
use Mouf\Mvc\Splash\Annotations\Delete;
use Mouf\Mvc\Splash\Annotations\Get;
use Mouf\Mvc\Splash\Annotations\Post;
use Mouf\Mvc\Splash\Annotations\Put;
use Mouf\Mvc\Splash\Annotations\Title;
use ReflectionMethod;

/**
 * This class is in charge of analyzing a controller instance and returning the routes it contains.
 */
class ControllerAnalyzer
{
    private $container;

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
     * @param ParameterFetcherRegistry $parameterFetcherRegistry
     * @param Reader                   $annotationReader         A Doctrine annotation reader
     */
    public function __construct(ContainerInterface $container, ParameterFetcherRegistry $parameterFetcherRegistry, Reader $annotationReader)
    {
        $this->container = $container;
        $this->parameterFetcherRegistry = $parameterFetcherRegistry;
        $this->annotationReader = $annotationReader;
    }

    /**
     * Returns is a given class name is a controller or not (whether it contains \@Action or \@URL annotations).
     *
     * @param string $className
     *
     * @return bool
     */
    public function isController(string $className) : bool
    {
        $refClass = new \ReflectionClass($className);


        foreach ($refClass->getMethods() as $refMethod) {
            $actionAnnotation = $this->annotationReader->getMethodAnnotation($refMethod, Action::class);
            if ($actionAnnotation) {
                return true;
            }
            $urlAnnotation = $this->annotationReader->getMethodAnnotation($refMethod, URL::class);
            if ($urlAnnotation) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns an array of SplashRoute for the controller passed in parameter.
     *
     * @param object $controller
     *
     * @return SplashRoute[]
     *
     * @throws \Mouf\Mvc\Splash\Utils\SplashException
     */
    public function analyzeController(string $controllerInstanceName) : array
    {
        // Let's analyze the controller and get all the @Action annotations:
        $urlsList = array();

        $controller = $this->container->get($controllerInstanceName);

        $refClass = new \ReflectionClass($controller);

        foreach ($refClass->getMethods() as $refMethod) {
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
                $urlsList[] = new SplashRoute($url, $controllerInstanceName, $refMethod->getName(), $title, $refMethod->getDocComment(), $this->getSupportedHttpMethods($refMethod), $parameters, $filters, $refClass->getFileName());
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
                $urlsList[] = new SplashRoute($url, $controllerInstanceName, $refMethod->getName(), $title, $refMethod->getDocComment(), $this->getSupportedHttpMethods($refMethod), $parameters, $filters, $refClass->getFileName());
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
    private function readPrivateProperty($object, string $property)
    {
        $reflectionClass = new \ReflectionClass($object);
        $reflectionProperty = null;
        do {
            if ($reflectionClass->hasProperty($property)) {
                $reflectionProperty = $reflectionClass->getProperty($property);
            }
            $reflectionClass = $reflectionClass->getParentClass();
        } while ($reflectionClass);

        if ($reflectionProperty === null) {
            throw new \InvalidArgumentException("Unable to find property '".$property.'" in object of class '.get_class($object).". Please check your @URL annotation.");
        }

        $reflectionProperty->setAccessible(true);
        return $reflectionProperty->getValue($object);
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
}
