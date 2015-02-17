<?php
namespace Mouf\Mvc\Splash\Routers;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Mouf\Utils\Cache\CacheInterface;
use Psr\Log\LoggerInterface;
use Mouf\Utils\Common\ConditionInterface\ConditionInterface;

class CacheRouter implements HttpKernelInterface
{
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

    public function __construct(HttpKernelInterface $fallBackRouter, CacheInterface $cache, LoggerInterface $log, ConditionInterface $cacheCondition)
    {
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
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        $requestMethod = $request->getMethod();
        $key = str_replace(['\\', '/', ':','*','?','"','<','>', "|"], "_", $request->getRequestUri() . $request->getQueryString());

        if ($this->cacheCondition->isOk() && $requestMethod == "GET") {
            $cacheResponse = $this->cache->get($key);
            if ($cacheResponse) {
                $this->log->debug("Cache HIT on $key");

                return $cacheResponse;
            } else {
                $this->log->debug("Cache MISS on key $key");
                $response = $this->fallBackRouter->handle($request, $type, $catch);

                $noCache = false;
                $headers = $response->headers;
                foreach ($headers as $innerHeader) {
                    foreach ($innerHeader as $value) {
                        if ($value == "Mouf-Cache-Control: no-cache") {
                            $noCache = true;
                        }
                    }
                }

                if ($noCache) {
                    $this->log->debug("Mouf NO CACHE specified, not storing $key");
                } else {
                    $ttl = null;
                    $maxAge = $response->getMaxAge();
                    $expires = $response->getExpires();
                    if ($maxAge) {
                        $this->log->debug("MaxAge specified : $maxAge");
                        $ttl = $maxAge;
                    } elseif ($expires) {
                        $this->log->debug("Expires specified : $expires");
                        $ttl = date_diff($expires, new \DateTime())->s;
                    }

                    if ($ttl) {
                        $this->log->debug("TTL is  : $ttl");
                    }

                    $this->cache->set($key, $response, $ttl);
                    $this->log->debug("Cache STORED on key $key");
                }

                return $response;
            }
        } else {
            $this->log->debug("NO Cache for $key");

            return $this->fallBackRouter->handle($request, $type, $catch);
        }
    }

}
