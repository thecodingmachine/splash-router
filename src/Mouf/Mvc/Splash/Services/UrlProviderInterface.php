<?php

namespace Mouf\Mvc\Splash\Services;

/**
 * This interface is implemented by any class that wants to open URL accesses to your application.
 *
 * @author David Négrier
 */
interface UrlProviderInterface
{
    /**
     * Returns the list of URLs that can be accessed, and the function/method that should be called when the URL is called.
     *
     * @param string $instanceName The identifier for this object in the container.
     *
     * @return SplashRoute[]
     */
    public function getUrlsList($instanceName);

    /**
     * Returns a unique tag representing the list of SplashRoutes returned.
     * If the tag changes, the cache is flushed by Splash.
     *
     * Important! This must be quick to compute.
     *
     * @return mixed
     */
    public function getExpirationTag() : string;
}
