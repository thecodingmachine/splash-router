<?php

namespace Mouf\Mvc\Splash\Routers;

use Mouf\Utils\Common\ConditionInterface\ConditionInterface;
use Zend\Stratigility\ErrorMiddlewareInterface;

/**
 * The ErrorRouter class wraps a PSR-7 middleware for handling errors with the associated path.
 */
class ErrorRouter implements RouterInterface
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var ErrorMiddlewareInterface
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
     * @param ErrorMiddlewareInterface|callable $middleware      The PSR-7 middleware to call
     * @param string                            $path            The path to that middleware (defaults to /).
     * @param ConditionInterface                $enableCondition Whether the middleware must be enabled or not.
     */
    public function __construct($middleware, $path = '/', ConditionInterface $enableCondition = null)
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
     * @return ErrorMiddlewareInterface
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
