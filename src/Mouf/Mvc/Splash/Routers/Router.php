<?php

namespace Mouf\Mvc\Splash\Routers;

use Mouf\Utils\Common\ConditionInterface\ConditionInterface;
use Zend\Stratigility\MiddlewareInterface;

/**
 * The Router class wraps a PSR-7 middleware with the associated path.
 */
class Router implements RouterInterface
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var MiddlewareInterface
     */
    private $middleware;

    /**
     * Whether the middleware must be enabled or not.
     *
     * @var ConditionInterface
     */
    private $enableCondition;

    /**
     * @Important IfSet
     *
     * @param MiddlewareInterface $middleware      The PSR-7 middleware to call
     * @param string              $path            The path to that middleware (defaults to /).
     * @param ConditionInterface  $enableCondition Whether the middleware must be enabled or not.
     */
    public function __construct(MiddlewareInterface $middleware, $path = '/', ConditionInterface $enableCondition = null)
    {
        $this->path = $path;
        $this->middleware = $middleware;
        $this->enableCondition = $enableCondition;
    }

    /**
     * The path to that middleware (defaults to /).
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * The PSR-7 middleware to call.
     *
     * @return MiddlewareInterface
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }

    /**
     * If this returns false, the router is skipped.
     *
     * @return bool
     */
    public function isActive()
    {
        if ($this->enableCondition !== null && $this->enableCondition->isOk() === false) {
            return false;
        } else {
            return true;
        }
    }
}
