<?php

namespace Mouf\Mvc\Splash\Routers;

use Mouf\Mvc\Splash\Controllers\Http500HandlerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Mouf\Mvc\Splash\Services\SplashUtils;
use Zend\Stratigility\ErrorMiddlewareInterface;

/**
 * This router returns transforms exceptions into HTTP 500 pages, based on the configured error controller.
 *
 * @author Kevin Nguyen
 * @author David Négrier
 */
class ExceptionRouter implements ErrorMiddlewareInterface
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
     * @param HttpKernelInterface $router The default router (the router we will catch exceptions from).
     * @param LoggerInterface     $log    Logger to log errors.
     */
    public function __construct(Http500HandlerInterface $errorController, LoggerInterface $log = null)
    {
        $this->errorController = $errorController;
        $this->log = $log;
    }

    /**
     * Actually handle the exception depending.
     *
     * @param \Exception $e
     *
     * @return ResponseInterface
     */
    private function handleException(\Exception $e)
    {
        if ($this->log != null) {
            $this->log->error('Exception thrown inside a controller.', array(
                    'exception' => $e,
            ));
        } else {
            // If no logger is set, let's log in PHP error_log
            error_log($e->getMessage().' - '.$e->getTraceAsString());
        }

        $response = SplashUtils::buildControllerResponse(
            function () use ($e) {
                return $this->errorController->serverError($e);
            }
        );

        return $response;
    }

    /**
     * Process an incoming error, along with associated request and response.
     *
     * Accepts an error, a server-side request, and a response instance, and
     * does something with them; if further processing can be done, it can
     * delegate to `$out`.
     *
     * @see MiddlewareInterface
     *
     * @param mixed         $error
     * @param Request       $request
     * @param Response      $response
     * @param null|callable $out
     *
     * @return null|Response
     */
    public function __invoke($error, Request $request, Response $response, callable $out = null)
    {
        return $this->handleException($error);
    }
}
