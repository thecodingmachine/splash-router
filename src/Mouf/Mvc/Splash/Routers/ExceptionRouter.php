<?php
namespace Mouf\Mvc\Splash\Routers;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Mouf\Mvc\Splash\Controllers\Http500HandlerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Mouf\Mvc\Splash\Services\SplashUtils;

/**
 * This router returns transforms exceptions into HTTP 500 pages, based on the configured error controller.
 *
 * @author Kevin Nguyen
 * @author David Négrier
 */
class ExceptionRouter implements HttpKernelInterface {
	
	/**
	 * The logger
	 *
	 * @var LoggerInterface
	 */
	private $log;
	
	/**
	 * @var HttpKernelInterface
	 */
	private $router;
	
	/**
	 * The controller that will display 500 errors
	 * @var Http500HandlerInterface
	 */
	private $errorController;
	
	/**
	 * The "500" message
	 * @var string|ValueInterface
	 */
	private $message = "Page not found";
	
	/**
	 * @Important
	 * @param HttpKernelInterface $router The default router (the router we will catch exceptions from).
	 * @param LoggerInterface $log Logger to log errors.
	 * @param bool $debugMode Whether we should print debug backtrace or not
	 */
	public function __construct(HttpKernelInterface $router, Http500HandlerInterface $errorController, LoggerInterface $log = null){
		$this->router = $router;
		$this->errorController = $errorController;
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
	 * @throws \Exception When an Exception occurs during processing (and $catch is set to false)
	 */
	public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true){
		if ($catch){
			try {
				return $this->router->handle($request, $type, false);
			} catch (\Exception $e) {
				return $this->handleException($e);
			}
		}else{
			return $this->router->handle($request, $type);
		}
	}
	
	/**
	 * Actually handle the exception depending
	 * @param \Exception $e
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	private function handleException(\Exception $e) {
		if ($this->log != null) {
			if ($this->log instanceof LogInterface) {
				$this->log->error($e);
			} else {
				$this->log->error("Exception thrown inside a controller.", array(
						'exception' => $e
				));
			}
		} else {
			// If no logger is set, let's log in PHP error_log
			error_log($e->getMessage()." - ".$e->getTraceAsString());
		}
	
		$response = SplashUtils::buildControllerResponse(
			function () use ($e) {
				return $this->errorController->serverError($e);
			}
		);

		return $response;
	}
	
	/**
	 * The "500" message
	 * @param string|ValueInterface $message
	 */
	public function setMessage($message){
		$this->message = $message;
	}
	
}