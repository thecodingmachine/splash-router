<?php

namespace Mouf\Mvc\Splash;

use Mouf\Utils\Action\ActionInterface;
use Mouf\Mvc\Splash\Services\SplashRoute;
use Mouf\Mvc\Splash\Services\UrlProviderInterface;
use Mouf\Utils\Common\UrlInterface;

/**
 * This class represents a single URL that can be bound to a specific behaviour.
 *
 * @author David Negrier
 */
class UrlEntryPoint implements UrlProviderInterface, UrlInterface
{
    private $url;
    private $actions;

    /**
     * Construct the object passing in parameter the URL and the list of actions to perform.
     *
     * @param string                 $url     The URL to bind to. It should
     * @param array<ActionInterface> $actions The list of actions to perform when the URL is called.
     */
    public function __construct($url, array $actions = array())
    {
        $this->url = $url;
        $this->actions = $actions;
    }

    /**
     * Method called when the URL is called.
     */
    public function action()
    {
        foreach ($this->actions as $action) {
            $action->run();
        }
    }

    /**
     * Returns the list of URLs that can be accessed, and the function/method that should be called when the URL is called.
     *
     * @param string $instanceName The identifier for this object in the container.
     *
     * @return SplashRoute[]
     */
    public function getUrlsList($instanceName)
    {
        $route = new SplashRoute($this->url, $instanceName, 'action', null, null);

        return array($route);
    }

    /* (non-PHPdoc)
     * @see \Mouf\Utils\Common\UrlInterface::getUrl()
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Returns a unique tag representing the list of SplashRoutes returned.
     * If the tag changes, the cache is flushed by Splash.
     *
     * Important! This must be quick to compute.
     *
     * @return mixed
     */
    public function getExpirationTag() : string
    {
        return $this->url;
    }
}
