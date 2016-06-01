<?php

namespace Mouf\Mvc\Splash\Routers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Mouf\Utils\Cache\CacheInterface;
use Psr\Log\LoggerInterface;
use Mouf\Utils\Common\ConditionInterface\ConditionInterface;
use Zend\Stratigility\MiddlewareInterface;

class CacheRouter implements MiddlewareInterface
{
    /**
     * @var CacheInterface
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

    public function __construct(CacheInterface $cache, LoggerInterface $log, ConditionInterface $cacheCondition)
    {
        $this->cache = $cache;
        $this->cacheCondition = $cacheCondition;
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
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param null|callable          $out
     *
     * @return null|ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $out = null)
    {
        $requestMethod = $request->getMethod();
        $key = str_replace(['\\', '/', ':', '*', '?', '"', '<', '>', '|'], '_', $request->getUri()->getPath().'?'.$request->getUri()->getQuery());

        if ($this->cacheCondition->isOk() && $requestMethod == 'GET') {
            $cacheResponse = $this->cache->get($key);
            if ($cacheResponse) {
                $this->log->debug("Cache HIT on $key");

                return $cacheResponse;
            } else {
                $this->log->debug("Cache MISS on key $key");
                $response = $out($request, $response);

                $noCache = false;
                if ($response->hasHeader('Mouf-Cache-Control') && $response->getHeader('Mouf-Cache-Control')[0] == 'no-cache') {
                    $noCache = true;
                }

                if ($noCache) {
                    $this->log->debug("Mouf NO CACHE header found, not storing '$key'");
                } else {
                    $ttl = null;

                    // TODO: continue here!
                    // Use PSR-7 response to analyze maxage and expires...
                    // ...or... use a completely different HTTP cache implementation!!!
                    // There must be one around for PSR-7!

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

                    // Make sure the response is serializable
                    $serializableResponse = new Response();
                    $serializableResponse->headers = $response->headers;

                    ob_start();
                    $response->sendContent();
                    $content = ob_get_clean();

                    $serializableResponse->setContent($content);

                    $this->cache->set($key, $serializableResponse, $ttl);
                    $this->log->debug("Cache STORED on key $key");
                    $response = $serializableResponse;
                }

                return $response;
            }
        } else {
            $this->log->debug("No cache for $key");

            return $this->fallBackRouter->handle($request, $type, $catch);
        }
    }
}
