<?php

namespace Mouf\Mvc\Splash\Services;

use Mouf\Mvc\Splash\Utils\SplashException;

/**
 * A callback used to access a page.
 *
 * @author David
 */
class SplashRoute implements SplashRouteInterface
{
    private $url;

    /**
     * List of HTTP methods allowed for this callback.
     * If empty, all methods are allowed.
     *
     * @var array<string>
     */
    private $httpMethods;

    private $controllerInstanceName;

    private $methodName;

    private $title;

    private $fullComment;

    /**
     * An ordered list of parameters.
     * The first parameter to be passed to the method will be fetched from values set $parameters[0], etc...
     *
     *
     * @var array<SplashParameterFetcherInterface>
     */
    private $parameters;

    /**
     * A list of all filters to apply to the route.
     *
     * @var array An array of filters.
     */
    private $filters;

    /**
     * The list of parameters matched during the route.
     * This is filled at runtime, by the SplashUrlNode class.
     *
     * @var array<string, string>
     */
    private $filledParameters = array();
    // Question: abstraire SplashRoute et rajouter un getCallbackHandler???

    /**
     * The file that contains the controller class.
     * Used to invalidate the cache.
     *
     * @var string
     */
    private $fileName;

    /**
     * @var int
     */
    private $fileModificationTime;

    public function __construct(string $url, string $controllerInstanceName, string $methodName, string $title = null, string $fullComment = null, array $httpMethods = array(), array $parameters = array(), array $filters = array(), string $fileName = null)
    {
        $this->url = $url;
        $this->httpMethods = $httpMethods;
        $this->controllerInstanceName = $controllerInstanceName;
        $this->methodName = $methodName;
        $this->title = $title;
        $this->fullComment = $fullComment;
        $this->parameters = $parameters;
        $this->filters = $filters;

        if ($fileName !== null) {
            $this->fileName = $fileName;
            $this->fileModificationTime = filemtime($fileName);
            if ($this->fileModificationTime === false) {
                throw new SplashException(sprintf('Could not find file modification time for "%s"', $this->fileName));
            }
        }
    }

    /**
     * @return mixed
     */
    public function getUrl() : string
    {
        return $this->url;
    }

    /**
     * List of HTTP methods allowed for this callback.
     * If empty, all methods are allowed.
     *
     * @return string[]
     */
    public function getHttpMethods() : array
    {
        return $this->httpMethods;
    }

    /**
     * @return string
     */
    public function getControllerInstanceName() : string
    {
        return $this->controllerInstanceName;
    }

    /**
     * @return string
     */
    public function getMethodName() : string
    {
        return $this->methodName;
    }

    /**
     * @return string
     */
    public function getTitle() : string
    {
        return $this->title;
    }

    /**
     * @return string|null
     */
    public function getFullComment()
    {
        return $this->fullComment;
    }

    /**
     * @return array
     */
    public function getParameters() : array
    {
        return $this->parameters;
    }

    /**
     * @return array
     */
    public function getFilters() : array
    {
        return $this->filters;
    }

    /**
     * @return string[]
     */
    public function getFilledParameters() : array
    {
        return $this->filledParameters;
    }

    public function setFilledParameters(array $parameters)
    {
        $this->filledParameters = $parameters;
    }

    /**
     * Checks if the data stored in this route is fresh or not (it comes from the cache).
     *
     * @return bool
     */
    public function isCacheValid() : bool
    {
        if ($this->fileName === null) {
            return true;
        }

        return $this->fileModificationTime === filemtime($this->fileName);
    }
}
