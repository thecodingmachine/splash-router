<?php

namespace Mouf\Mvc\Splash\Routers;

use Mouf\Mvc\Splash\Controllers\Http500HandlerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Mouf\Mvc\Splash\Services\SplashUtils;

/**
 * This router transforms exceptions into HTTP 500 pages, based on the configured error controller.
 *
 * @author Kevin Nguyen
 * @author David Négrier
 */
class ExceptionRouter implements MiddlewareInterface
{
    /**
     * The logger.
     *
     * @var LoggerInterface
     */
    private $log;

    /**
     * The controller that will display 500 errors.
     *
     * @var Http500HandlerInterface
     */
    private $errorController;

    /**
     * @Important
     *
     * @param Http500HandlerInterface $errorController The controller in charge of displaying the HTTP 500 error.
     * @param LoggerInterface $log Logger to log errors.
     */
    public function __construct(Http500HandlerInterface $errorController, LoggerInterface $log = null)
    {
        $this->errorController = $errorController;
        $this->log = $log;
    }

    /**
     * Actually handle the exception.
     *
     * @param \Throwable $t
     * @param Request $request
     * @return ResponseInterface
     */
    private function handleException(\Throwable $t, Request $request)
    {
        if ($this->log !== null) {
            $this->log->error('Exception thrown inside a controller.', array(
                    'exception' => $t,
            ));
        } else {
            // If no logger is set, let's log in PHP error_log
            error_log($t->getMessage().' - '.$t->getTraceAsString());
        }

        $response = SplashUtils::buildControllerResponse(
            function () use ($t, $request) {
                return $this->errorController->serverError($t, $request);
            }
        );

        return $response;
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param Request $request
     *
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(Request $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (\Throwable $t) {
            return $this->handleException($t, $request);
        }
    }
}
