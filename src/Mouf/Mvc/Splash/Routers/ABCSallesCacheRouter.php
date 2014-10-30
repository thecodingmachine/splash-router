<?php
namespace Mouf\Mvc\Splash\Routers;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Mouf\Utils\Cache\CacheInterface;
use Psr\Log\LoggerInterface;
use Mouf\Utils\Common\ConditionInterface\ConditionInterface;

class ABCSallesCacheRouter implements HttpKernelInterface {
	
	/**
	 * The router that will handle the request if cache miss
	 * @var HttpKernelInterface
	 */
	private $fallBackRouter;
	
	/**
	 * @CacheInterface
	 */
	private $cache;
	
	/**
	 * @var ConditionInterface
	 */
	private $cacheCondition;
	
	/**
	 * @var LoggerInterface
	 */
	private $log;
	
	public function __construct(HttpKernelInterface $fallBackRouter, CacheInterface $cache, LoggerInterface $log, ConditionInterface $cacheCondition){
		$this->fallBackRouter = $fallBackRouter;
		$this->cache = $cache;
		$this->cacheCondition = $cacheCondition;
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
	public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true){
		$requestMethod = $request->getMethod();
		$key = $request->getRequestUri() . $request->getQueryString();

		if ($this->cacheCondition->isOk() && $requestMethod == "GET"){
			$cacheResponse = $this->cache->get($key);
			if ($cacheResponse){
				$this->log->debug("Cache HIT on $key");
				return $cacheResponse;
			}else{
				$this->log->debug("Cache MISS on key $key");
				$response = $this->fallBackRouter->handle($request, $type, $catch);
				$this->cache->set($key, $response);
				$this->log->debug("Cache STORED on key $key");
				return $response;
			}
		}else{
			$this->log->debug("NO Cache for $key");
			return $this->fallBackRouter->handle($request, $type, $catch);
		}
	}
	
}