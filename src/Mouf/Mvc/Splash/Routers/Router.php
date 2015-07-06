<?php

namespace Mouf\Mvc\Splash\Routers;

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
     * @Important
     *
     * @param MiddlewareInterface $middleware The PSR-7 middleware to call
     * @param string              $path       The path to that middleware (defaults to /).
     */
    public function __construct(MiddlewareInterface $middleware, $path = '/')
    {
        $this->path = $path;
        $this->middleware = $middleware;
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
}
