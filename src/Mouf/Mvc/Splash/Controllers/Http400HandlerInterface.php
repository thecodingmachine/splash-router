<?php

namespace Mouf\Mvc\Splash\Controllers;

use Mouf\Mvc\Splash\Exception\BadRequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Classes implementing this interface can be used when a HTTP 400 error is triggered.
 *
 * The class must be registered in the "splashDefaultRouter" instance to be called.
 *
 * @author David Négrier
 */
interface Http400HandlerInterface
{
    /**
     * This function is called when a HTTP 400 error is triggered by the user.
     *
     * @param BadRequestException    $exception
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function badRequest(BadRequestException $exception, ServerRequestInterface $request) : ResponseInterface;
}
