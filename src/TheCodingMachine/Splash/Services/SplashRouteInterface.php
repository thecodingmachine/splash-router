<?php

namespace TheCodingMachine\Splash\Services;

/**
 * A callback used to access a page.
 *
 * @author David Négrier
 */
interface SplashRouteInterface
{
    public function getUrl() : string;

    /**
     * List of HTTP methods allowed for this callback.
     * If empty, all methods are allowed.
     *
     * @return string[]
     */
    public function getHttpMethods() : array;

    /**
     * @return string
     */
    public function getControllerInstanceName() : string;

    /**
     * @return string
     */
    public function getMethodName() : string;

    /**
     * @return string
     */
    public function getTitle();

    /**
     * @return string|null
     */
    public function getFullComment();

    /**
     * @return array
     */
    public function getParameters() : array;

    /**
     * @return array
     */
    public function getFilters() : array;

    /**
     * @return string[]
     */
    public function getFilledParameters() : array;

    public function setFilledParameters(array $parameters);

    /**
     * Checks if the data stored in this route is fresh or not (it comes from the cache).
     *
     * @return bool
     */
    public function isCacheValid() : bool;
}
