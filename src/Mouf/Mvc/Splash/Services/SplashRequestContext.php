<?php

namespace Mouf\Mvc\Splash\Services;

use Mouf\Mvc\Splash\Exception\SplashMissingParameterException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * This class represents the context of a request so far (so basically, it contains the
 * HTTP request and any additional parameters from the URL analysis).
 *
 * @author David Negrier
 */
class SplashRequestContext
{
    private $urlParameters = array();
    private $request;

    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Add a new parameter.
     *
     * @param string $key
     * @param string $value
     */
    public function addUrlParameter($key, $value)
    {
        $this->urlParameters[$key] = $value;
    }

    /**
     * Sets all parameters at once.
     *
     * @param array $urlParameters
     */
    public function setUrlParameters(array $urlParameters)
    {
        $this->urlParameters = $urlParameters;
    }

    /**
     * Returns the list of parameters seen while analysing the URL.
     *
     * @return array<string, string>
     */
    public function getUrlParameters()
    {
        return $this->urlParameters;
    }

    /**
     * Returns the request.
     *
     * @return ServerRequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    public function hasParameter($key) : bool
    {
        if (isset($this->urlParameters[$key])) {
            return true;
        } elseif (isset($this->request->getParsedBody()[$key])) {
            return true;
        } elseif (isset($this->request->getQueryParams()[$key])) {
            return true;
        } elseif (isset($this->request->getUploadedFiles()[$key])) {
            return true;
        }

        return false;
    }

    /**
     * Scan the URL parameters and the request parameters and return the given parameter (or a default value).
     *
     * @param string $key
     *
     * @return mixed
     *
     * @throws SplashMissingParameterException
     */
    public function getParameter(string $key, bool $compulsory, $default = null)
    {
        if (isset($this->urlParameters[$key])) {
            $value = $this->urlParameters[$key];
        } else {
            $postVals = $this->request->getParsedBody();
            $value = null;
            if (isset($postVals[$key])) {
                $value = $postVals[$key];
            } else {
                $getVals = $this->request->getQueryParams();
                if (isset($getVals[$key])) {
                    $value = $getVals[$key];
                } else {
                    $uploadedFiles = $this->request->getUploadedFiles();
                    if (isset($uploadedFiles[$key])) {
                        $value = $uploadedFiles[$key];
                    }
                }
            }
        }
        if ($value !== null) {
            return $value;
        } elseif (!$compulsory) {
            return $default;
        } else {
            throw SplashMissingParameterException::create($key);
        }
    }
}
