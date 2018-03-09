<?php

namespace TheCodingMachine\Splash\Fixtures;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TheCodingMachine\Splash\Filters\FilterInterface;

/**
 * @Annotation
 */
class TestFilter implements FilterInterface
{
    private $params;

    /**
     * TestFilter constructor.
     *
     * @param $params
     */
    public function __construct($params)
    {
        $this->params = $params;
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next, ContainerInterface $container): ResponseInterface
    {
        $request = $request->withQueryParams(array_merge($request->getQueryParams(), $this->params));
        return $next->handle($request);
    }
}
