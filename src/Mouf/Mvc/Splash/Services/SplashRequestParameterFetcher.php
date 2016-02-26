<?php

namespace Mouf\Mvc\Splash\Services;

use Mouf\Utils\Common\Validators\ValidatorInterface;

/**
 * This class fetches the parameter from the request.
 *
 * @author David Negrier
 */
class SplashRequestParameterFetcher implements SplashParameterFetcherInterface
{
    private $key;

    /**
     * Whether the parameter is compulsory or not.
     *
     * @var bool
     */
    private $compulsory;

    /**
     * The default value for the parameter.
     *
     * @var mixed
     */
    private $default;

    /**
     * Constructor.
     *
     * @param string $key The name of the parameter to fetch.
     */
    public function __construct($key, $compulsory = true, $default = null)
    {
        $this->key = $key;
        $this->compulsory = $compulsory;
        $this->default = $default;
    }

    /**
     * Get the name of the parameter (only for error handling purposes).
     *
     * @return string
     */
    public function getName()
    {
        return $this->key;
    }

    /**
     * We pass the context of the request, the object returns the value to fill.
     *
     * @param SplashRequestContext $context
     *
     * @return mixed
     */
    public function fetchValue(SplashRequestContext $context)
    {
        $request = $context->getRequest();
        $postVals = $request->getParsedBody();
        $value = null;
        if (isset($postVals[$this->key])) {
        	$value = $postVals[$this->key];
        }
        else
        {
            $getVals = $request->getQueryParams();
            if (isset($getVals[$this->key])) {
                $value = $getVals[$this->key];
            } else {
                $uploadedFiles = $request->getUploadedFiles();
                if (isset($uploadedFiles[$this->key])) {
                    $value = $uploadedFiles[$this->key];
                }
            }
        }
        if ($value !== null) {
            return $value;
        } elseif (!$this->compulsory) {
            return $this->default;
        } else {
            throw new SplashMissingParameterException($this->key);
        }
    }
}
