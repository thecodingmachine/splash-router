<?php

namespace Mouf\Mvc\Splash\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Classes implementing this interface can be used when a HTTP 404 error is triggered.
 *
 * The class must be registered in the "splashDefaultRouter" instance to be called.
 *
 * @author David Négrier
 */
interface Http404HandlerInterface
{
    /**
     * This function is called when a HTTP 404 error is triggered by the user.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function pageNotFound(ServerRequestInterface $request) : ResponseInterface;
}
