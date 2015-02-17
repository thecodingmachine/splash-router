<?php
namespace Mouf\Mvc\Splash\Routers;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Mouf\Mvc\Splash\Controllers\Http404HandlerInterface;
use Mouf\Utils\Value\ValueInterface;
use Mouf\Utils\Value\ValueUtils;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Mouf\Mvc\Splash\Services\SplashUtils;

/**
 * This router always returns a 404 page, based on the configured page not found controller.
 *
 * @author Kevin Nguyen
 * @author David Négrier
 */
class NotFoundRouter implements HttpKernelInterface
{
    /**
	 * The logger
	 *
	 * @var LoggerInterface
	 */
    private $log;

    /**
	 * The "404" message
	 * @var string|ValueInterface
	 */
    private $message = "Page not found";

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
	 * Handles a Request to convert it to a Response.
	 *
	 * When $catch is true, the implementation must catch all exceptions
	 * and do its best to convert them to a Response instance.
	 *
	 * @param Request $request A Request instance
	 * @param int     $type    The type of the request
	 *                          (one of HttpKernelInterface::MASTER_REQUEST or HttpKernelInterface::SUB_REQUEST)
	 * @param bool    $catch Whether to catch exceptions or not
	 *
	 * @return Response A Response instance
	 *
	 * @throws \Exception When an Exception occurs during processing
	 */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        if ($this->log) {
            $this->log->info("404 - Page not found on URL: ".$request->getRequestUri());
        }
        $message = ValueUtils::val($this->message);

        $response = SplashUtils::buildControllerResponse(
            function () use ($message) {
                return $this->pageNotFoundController->pageNotFound($message);
            }
        );

        return $response;
    }

    /**
	 * The "404" message
	 * @param string|ValueInterface $message
	 */
    public function setMessage($message)
    {
        $this->message = $message;
    }

}
