<?php

namespace Mouf\Mvc\Splash\Services;

/*
 * Utility class for filters.
 */
use Doctrine\Common\Annotations\AnnotationReader;
use ReflectionMethod;

class FilterUtils
{
    /**
     * Returns a list of filters instances, order by priority (higher priority first).
     * Note: a filter is an annotation that is also a middleware.
     *
     * The middleware must have a __invoke method with signature:
     *
     *     __invoke(ServerRequestInterface $request, $next, ContainerInterface $container)
     *
     * @param ReflectionMethod $refMethod the reference method extended object.
     * @param AnnotationReader $reader
     *
     * @return array Array of filter instances sorted by priority.
     */
    public static function getFilters(ReflectionMethod $refMethod, AnnotationReader $reader) : array
    {
        $filterArray = array();

        $refClass = $refMethod->getDeclaringClass();

        $parentsArray = array();
        $parentClass = $refClass;
        while ($parentClass !== false) {
            $parentsArray[] = $parentClass;
            $parentClass = $parentClass->getParentClass();
        }

        // Start with the most parent class and goes to the target class:
        for ($i = count($parentsArray) - 1; $i >= 0; --$i) {
            $class = $parentsArray[$i];
            /* @var $class ReflectionClass */
            $annotations = $reader->getClassAnnotations($class);

            foreach ($annotations as $annotation) {
                if (is_callable($annotation)) {
                    $filterArray[] = $annotation;
                }
            }
        }

        // Continue with the method (and eventually override class parameters)
        $annotations = $reader->getMethodAnnotations($refMethod);

        foreach ($annotations as $annotation) {
            if (is_callable($annotation)) {
                $filterArray[] = $annotation;
            }
        }

        return $filterArray;
    }
}
