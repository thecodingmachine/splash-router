<?php

namespace Mouf\Mvc\Splash\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Classes implementing this interface can be used when a HTTP 500 error is triggered.
 *
 * The class must be registered in the "splashDefaultRouter" instance to be called.
 *
 * @author David Négrier
 */
interface Http500HandlerInterface
{
    /**
     * This function is called when a HTTP 500 error is triggered by the server.
     *
     * @param \Throwable             $throwable
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function serverError(\Throwable $throwable, ServerRequestInterface $request) : ResponseInterface;
}
