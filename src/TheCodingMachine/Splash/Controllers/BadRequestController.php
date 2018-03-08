<?php

namespace TheCodingMachine\Splash\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TheCodingMachine\Splash\Exception\BadRequestException;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;

class BadRequestController implements Http400HandlerInterface
{
    public function badRequest(BadRequestException $e, ServerRequestInterface $request) : ResponseInterface
    {
        if($request->getHeader("Accept") === 'application/json') {
            return new JsonResponse("Bad request", 400);
        }
        return new HtmlResponse("Bad request", 400);

    }

}