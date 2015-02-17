<?php
namespace Mouf\Mvc\Splash;

use Mouf\Utils\Action\ActionInterface;

use Mouf\MoufManager;

use Mouf\Mvc\Splash\Services\SplashRoute;

use Mouf\Mvc\Splash\Services\UrlProviderInterface;

/**
 * This class represents a single URL that can be bound to a specific behaviour.
 *
 * @author David Negrier
 */
class Route implements UrlProviderInterface
{
    private $url;
    private $actions;

    /**
	 * Construct the object passing in parameter the URL and the list of actions to perform
	 *
	 * @param string $url The URL to bind to.
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
	 * @return array<SplashRoute>
	 */
    public function getUrlsList()
    {
        $instanceName = MoufManager::getMoufManager()->findInstanceName($this);

        $route = new SplashRoute($this->url, $instanceName, "action", null, null);

        return array($route);
    }
}
