<?php

namespace TheCodingMachine\Splash\Services;

/**
 * Classes implementing this interface are in charge of detecting controllers in containers.
 */
interface ControllerDetector
{
    /**
     * Returns a list of controllers.
     * It is the name of the controller (in the container) that is returned (not the container itself).
     *
     * @param ControllerAnalyzer $controllerAnalyzer
     *
     * @return string[]
     */
    public function getControllerIdentifiers(ControllerAnalyzer $controllerAnalyzer) : array;

    /**
     * Returns a unique tag representing the list of SplashRoutes returned.
     * If the tag changes, the cache is flushed by Splash.
     *
     * Important! This must be quick to compute.
     */
    public function getExpirationTag() : string;
}
