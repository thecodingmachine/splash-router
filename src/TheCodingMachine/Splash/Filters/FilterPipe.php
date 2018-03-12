<?php

namespace TheCodingMachine\Splash\Filters;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * The class used to go trough the filter list.
 * Inspired by Zend's MiddlewarePipe
 */
class FilterPipe implements RequestHandlerInterface
{
    /**
     * @var \SplQueue
     */
    private $queue;

    /**
     * @var RequestHandlerInterface
     */
    private $handler;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * FilterPipe constructor.
     * @param FilterInterface[] $filters
     * @param RequestHandlerInterface $handler
     * @param ContainerInterface $container
     */
    public function __construct(array $filters, RequestHandlerInterface $handler, ContainerInterface $container)
    {
        $this->queue = new \SplQueue();
        foreach ($filters as $filter) {
            $this->queue->enqueue($filter);
        }
        $this->handler = $handler;
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->queue->isEmpty()) {
            return $this->handler->handle($request);
        }
        $filter = $this->queue->dequeue();

        return $filter->process($request, $this, $this->container);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->process($request);
    }
}
