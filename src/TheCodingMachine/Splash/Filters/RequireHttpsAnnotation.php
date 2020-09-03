<?php

namespace TheCodingMachine\Splash\Filters;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TheCodingMachine\Splash\Utils\SplashException;
use Laminas\Diactoros\Response\RedirectResponse;

/**
 * Filter that requires the use of HTTPS (if enabled in the conf)
 * By passing @RequireHttps("force"), an Exception is thrown if the action is called in HTTP.
 * By passing @RequireHttps("no"), no test is performed.
 * By passing @RequireHttps("redirect"), the call is redirected to HTTPS. Does only work with GET requests.
 *
 * @Annotation
 */
class RequireHttpsAnnotation implements FilterInterface
{
    /**
     * The value passed to the filter.
     */
    protected $value;

    public function __construct(array $values)
    {
        $value = $values['value'];
        if ($value === 'force') {
            $this->value = 'force';
        } elseif (strpos($value, 'no') !== false) {
            $this->value = 'no';
        } elseif (strpos($value, 'redirect') !== false) {
            $this->value = 'redirect';
        }

        if ($this->value === null) {
            throw new SplashException('You need to specify a value (either "force", "no" or "redirect") to the @RequireHttpsAnnotation.');
        }
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next, ContainerInterface $container): ResponseInterface
    {
        $uri = $request->getUri();
        $scheme = $uri->getScheme();
        if ($scheme === 'http') {
            if ($request->getMethod() !== 'GET') {
                throw new SplashException('Only GET HTTP methods can be redirected to HTTPS');
            }
            $uri = $uri->withScheme('https');

            return new RedirectResponse($uri);
        }

        return $next->handle($request);
    }
}
