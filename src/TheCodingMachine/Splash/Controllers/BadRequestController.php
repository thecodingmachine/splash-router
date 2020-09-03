<?php

namespace TheCodingMachine\Splash\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TheCodingMachine\Splash\Exception\BadRequestException;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;

class BadRequestController implements Http400HandlerInterface
{
    public function badRequest(BadRequestException $e, ServerRequestInterface $request) : ResponseInterface
    {
        $acceptType = $request->getHeader('Accept');
        if (\is_array($acceptType) && \count($acceptType) > 0 && \strpos($acceptType[0], 'json') !== false) {
            return new JsonResponse("Bad request", 400);
        }
        return new HtmlResponse("Bad request", 400);
    }
}
