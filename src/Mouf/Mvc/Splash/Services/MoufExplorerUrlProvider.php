<?php
namespace Mouf\Mvc\Splash\Services;

use Mouf\MoufManager;

/**
 * This class scans the Mouf container in order to find all UrlProviderInterface instances.
 * Use it to discover instances.
 */
class MoufExplorerUrlProvider implements UrlProviderInterface
{

    /**
     * Returns the list of URLs that can be accessed, and the function/method that should be called when the URL is called.
     *
     * @param string $instanceName The identifier for this object in the container.
     * @return array <SplashRoute>
     */
    public function getUrlsList($instanceName)
    {
        $moufManager = MoufManager::getMoufManager();
        $instanceNames = $moufManager->findInstances('Mouf\\Mvc\\Splash\\Services\\UrlProviderInterface');

        $urls = array();

        foreach ($instanceNames as $instanceName) {
            $urlProvider = $moufManager->getInstance($instanceName);
            /* @var $urlProvider UrlProviderInterface */
            if ($urlProvider === $this) {
                continue;
            }
            $tmpUrlList = $urlProvider->getUrlsList($instanceName);
            $urls = array_merge($urls, $tmpUrlList);
        }

        return $urls;
    }
}
