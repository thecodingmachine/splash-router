<?php

namespace Mouf\Mvc\Splash\Routers;

use Mouf\Mvc\Splash\Controllers\Http404HandlerInterface;
use Mouf\Utils\Value\ValueInterface;
use Mouf\Utils\Value\ValueUtils;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Mouf\Mvc\Splash\Services\SplashUtils;
use Zend\Stratigility\MiddlewareInterface;

/**
 * This router always returns a 404 page, based on the configured page not found controller.
 *
 * @author Kevin Nguyen
 * @author David Négrier
 */
class NotFoundRouter implements MiddlewareInterface
{
    /**
     * The logger.
     *
     * @var LoggerInterface
     */
    private $log;

    /**
     * The "404" message.
     *
     * @var string|ValueInterface
     */
    private $message = 'Page not found';

    /**
     * @var Http404HandlerInterface
     */
    private $pageNotFoundController;

    public function __construct(Http404HandlerInterface $pageNotFoundController, LoggerInterface $log = null)
    {
        $this->pageNotFoundController = $pageNotFoundController;
        $this->log = $log;
    }

    /**
     * Process an incoming request and/or response.
     *
     * Accepts a server-side request and a response instance, and does
     * something with them.
     *
     * If the response is not complete and/or further processing would not
     * interfere with the work done in the middleware, or if the middleware
     * wants to delegate to another process, it can use the `$out` callable
     * if present.
     *
     * If the middleware does not return a value, execution of the current
     * request is considered complete, and the response instance provided will
     * be considered the response to return.
     *
     * Alternately, the middleware may return a response instance.
     *
     * Often, middleware will `return $out();`, with the assumption that a
     * later middleware will return a response.
     *
     * @param Request       $request
     * @param Response      $response
     * @param null|callable $out
     *
     * @return null|Response
     */
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        if ($this->log) {
            $this->log->info('404 - Page not found on URL: '.$request->getUri()->getPath());
        }
        $message = ValueUtils::val($this->message);

        $response = SplashUtils::buildControllerResponse(
            function () use ($message) {
                return $this->pageNotFoundController->pageNotFound($message);
            }
        );

        return $response;
    }
}
