<?php
namespace Mouf\Mvc\Splash\Services;

/**
 * Objects implementing this interface can be used to fetch the value to pass to a parameter in a method called by Splash.
 *
 * @author David Negrier
 */
interface SplashParameterFetcherInterface
{
    /**
	 * Get the name of the parameter (only for error handling purposes).
	 *
	 * @return string
	 */
    public function getName();

    /**
	 * We pass the context of the request, the object returns the value to fill.
	 *
	 * @param SplashRequestContext $context
	 * @return mixed
	 */
    public function fetchValue(SplashRequestContext $context);
}
