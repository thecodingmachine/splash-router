<?php

/**
 * Created by PhpStorm.
 * User: david
 * Date: 23/06/15
 * Time: 15:10.
 */

namespace Mouf\Mvc\Splash\Routers;

use Zend\Stratigility\ErrorMiddlewareInterface;
use Zend\Stratigility\MiddlewareInterface;

/**
 * The classes implementing RouterInterface wrap a PSR-7 middleware with the associated path.
 */
interface RouterInterface
{
    /**
     * The path to that middleware (defaults to /).
     *
     * @return string
     */
    public function getPath();

    /**
     * The PSR-7 middleware to call.
     *
     * @return MiddlewareInterface|ErrorMiddlewareInterface
     */
    public function getMiddleware();
}
