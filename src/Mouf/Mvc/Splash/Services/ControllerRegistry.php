<?php

namespace Mouf\Mvc\Splash\Services;

use Mouf\Mvc\Splash\Annotations\Action;

/**
 * This class is in charge of registering controller's routes.
 */
class ControllerRegistry implements UrlProviderInterface
{
    private $controllers;

    /**
     * @var ControllerDetector
     */
    private $controllerDetector;

    /**
     * @var ControllerAnalyzer
     */
    private $controllerAnalyzer;

    /**
     * Initializes the registry with an array of container instances names.
     *
     * @param ControllerAnalyzer $controllerAnalyzer
     * @param string[]           $controllers        An array of controller instance name (as declared in the container)
     * @param ControllerDetector $controllerDetector
     */
    public function __construct(ControllerAnalyzer $controllerAnalyzer, array $controllers = [], ControllerDetector $controllerDetector = null)
    {
        $this->controllerAnalyzer = $controllerAnalyzer;
        $controllersArr = array_values($controllers);
        $this->controllers = array_combine($controllersArr, $controllersArr);
        $this->controllerDetector = $controllerDetector;
    }

    /**
     * Adds a container to the registry (by its instance name).
     * Note: any object that has a \@Action or \@URL annotation is a controller.
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
     *
     * @throws \Mouf\Mvc\Splash\Utils\SplashException
     */
    public function getUrlsList($instanceName)
    {
        // FIXME: $instanceName is no more needed! o_O
        $urlsList = [];

        if ($this->controllerDetector) {
            $detectedControllers = array_values($this->controllerDetector->getControllerIdentifiers($this->controllerAnalyzer));
            $detectedControllers = array_combine($detectedControllers, $detectedControllers);

            $controllers = $this->controllers + $detectedControllers;
        } else {
            $controllers = $this->controllers;
        }

        foreach ($controllers as $controllerInstanceName) {
            $routes = $this->controllerAnalyzer->analyzeController($controllerInstanceName);

            $urlsList = array_merge($urlsList, $routes);
        }

        return $urlsList;
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
        return implode('-/-', $this->controllers).($this->controllerDetector !== null ? $this->controllerDetector->getExpirationTag() : '');
    }
}
