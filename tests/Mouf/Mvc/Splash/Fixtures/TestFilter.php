<?php

namespace Mouf\Mvc\Splash\Fixtures;

use Interop\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @Annotation
 */
class TestFilter
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

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next, ContainerInterface $container)
    {
        $request = $request->withQueryParams(array_merge($request->getQueryParams(), $this->params));
        $response = $next($request, $response);

        return $response;
    }
}
