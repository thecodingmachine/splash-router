<?php

namespace Mouf\Mvc\Splash;

use Mouf\Mvc\Splash\Routers\RouterInterface;
use Mouf\Mvc\Splash\Controllers\Controller;
use Zend\Stratigility\MiddlewarePipe;

/**
 * The SplashMiddleware class is the root of the Splash framework.<br/>
 * It is in charge of binding an Url to a Controller.<br/>
 * There is one and only one instance of Splash per web application.<br/>
 * The name of the instance MUST be "splashMiddleware".<br/>
 * <br/>
 * The SplashMiddleware component has several ways to bind an URL to a Controller.<br/>
 * It can do so based on the @URL annotation, or based on the @Action annotation.<br/>
 * Check out the Splash documentation here:
 * <a href="https://github.com/thecodingmachine/mvc.splash/">https://github.com/thecodingmachine/mvc.splash/</a>.
 */
class SplashMiddleware extends MiddlewarePipe
{
    /**
     * @param RouterInterface[] $routers
     */
    public function __construct(array $routers)
    {
        parent::__construct();
        foreach ($routers as $router) {
            if ($router->isActive()) {
                $this->pipe($router->getPath(), $router->getMiddleware());
            }
        }
    }
}
