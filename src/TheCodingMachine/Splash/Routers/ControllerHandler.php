<?php

namespace TheCodingMachine\Splash\Routers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TheCodingMachine\Splash\Services\ParameterFetcherRegistry;
use TheCodingMachine\Splash\Services\SplashRequestContext;
use TheCodingMachine\Splash\Services\SplashRoute;
use TheCodingMachine\Splash\Services\SplashUtils;
use TheCodingMachine\Splash\Utils\SplashException;

/**
 * Class in charge of calling the controller after the filters have been applied by the splash router.
 */
class ControllerHandler implements RequestHandlerInterface
{

    /**
     * @var SplashRoute
     */
    private $splashRoute;
    /**
     * @var
     */
    private $controller;
    /**
     * @var ParameterFetcherRegistry
     */
    private $parameterFetcherRegistry;
    /**
     * @var string
     */
    private $mode;
    /**
     * @var bool
     */
    private $debug;

    public function __construct(SplashRoute $splashRoute, $controller, ParameterFetcherRegistry $parameterFetcherRegistry, string $mode, bool $debug)
    {
        $this->splashRoute = $splashRoute;
        $this->controller = $controller;
        $this->parameterFetcherRegistry = $parameterFetcherRegistry;
        $this->mode = $mode;
        $this->debug = $debug;
    }

    /**
     * Handle the request and return a response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Let's recreate a new context object (because request can be modified by the filters)
        $context = new SplashRequestContext($request);
        $context->setUrlParameters($this->splashRoute->getFilledParameters());
        // Let's pass everything to the controller:
        $args = $this->parameterFetcherRegistry->toArguments($context, $this->splashRoute->getParameters());
        $action = $this->splashRoute->getMethodName();
        $controller = $this->controller;

        try {
            $response = SplashUtils::buildControllerResponse(
                function () use ($controller, $action, $args) {
                    return $controller->$action(...$args);
                },
                $this->mode,
                $this->debug
            );
        } catch (SplashException $e) {
            throw new SplashException($e->getMessage(). ' (in '.$this->splashRoute->getControllerInstanceName().'->'.$this->splashRoute->getMethodName().')', $e->getCode(), $e);
        }
        return $response;
    }
}
