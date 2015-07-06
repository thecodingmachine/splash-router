<?php

namespace Mouf\Mvc\Splash\Routers;

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
     * @Important
     *
     * @param ErrorMiddlewareInterface $middleware The PSR-7 middleware to call
     * @param string                   $path       The path to that middleware (defaults to /).
     */
    public function __construct(ErrorMiddlewareInterface $middleware, $path = '/')
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
     * @return ErrorMiddlewareInterface
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }
}
