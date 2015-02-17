<?php
namespace Mouf\Mvc\Splash\Services;

use Symfony\Component\HttpFoundation\Request;
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

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
	 * Add a new parameter
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
	 * Returns the request
	 * @return Request
	 */
    public function getRequest()
    {
        return $this->request;
    }
}
