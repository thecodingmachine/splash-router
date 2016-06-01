<?php

namespace Mouf\Mvc\Splash\Filters;

use Interop\Container\ContainerInterface;
use Mouf\Mvc\Splash\Utils\SplashException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\RedirectResponse;

/**
 * Filter that will bring the user back to HTTP if the user is in HTTPS.
 * The port can be specified in parameter if needed (@RedirectToHttpAnnotation(port=8080))
 * Works only with GET requests. If another request is performed, an exception will be thrown.
 *
 * @Annotation
 */
class RedirectToHttpAnnotation
{
    /**
     * The value passed to the filter.
     */
    protected $port;

    public function __construct(array $values)
    {
        $this->port = $values['port'] ?? 80;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next, ContainerInterface $container)
    {
        $uri = $request->getUri();
        $scheme = $uri->getScheme();
        if ($scheme === 'https') {
            if ($request->getMethod() !== 'GET') {
                throw new SplashException('Only GET HTTP methods can be redirected to HTTP');
            }
            $uri = $uri->withScheme('http')->withPort($this->port);

            return new RedirectResponse($uri);
        }

        return $next($request, $response);
    }
}
