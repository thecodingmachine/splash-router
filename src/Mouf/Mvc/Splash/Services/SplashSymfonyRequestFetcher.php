<?php
namespace Mouf\Mvc\Splash\Services;

/**
 * This class is used to inject the Symfony HttpFoundation Request object into the controller's action arguments.
 *
 * @author David Negrier
 */
class SplashSymfonyRequestFetcher implements SplashParameterFetcherInterface
{
    /**
	 * Constructor
	 * @param string $key The name of the parameter to fetch.
	 */
    public function __construct($key)
    {
        $this->key = $key;
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
	 * @return mixed
	 */
    public function fetchValue(SplashRequestContext $context)
    {
        return $context->getRequest();
    }
}
