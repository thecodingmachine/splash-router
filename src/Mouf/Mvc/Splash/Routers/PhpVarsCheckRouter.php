<?php

namespace Mouf\Mvc\Splash\Routers;

use Mouf\Mvc\Splash\Utils\SplashException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Zend\Stratigility\MiddlewareInterface;

/**
 * This router :
 *  - just checks that some PHP settings are not exeeded : max_input_vars, max_post_size
 *  - doesn't actually 'routes' the request. It's more like a filter to me applied and check the request.
 *  - should be placed BEFORE the effective applications router and AFTER the Exceptions handling routers.
 *
 * @author Kevin Nguyen
 */
class PhpVarsCheckRouter implements MiddlewareInterface
{
    /**
     * The logger used by Splash.
     *
     * @var LoggerInterface
     */
    private $log;

    /**
     * A simple counter to check requests' length (GET, POST, REQUEST).
     *
     * @var int
     */
    private $count;

    /**
     * @Important
     *
     * @param LoggerInterface $log The logger used by Splash
     */
    public function __construct(LoggerInterface $log = null)
    {
        $this->log = $log;
    }

    /**
     * Get the min in 2 values if there exist.
     *
     * @param int $val1
     * @param int $val2
     *
     * @return int|NULL
     */
    private function getMinInConfiguration($val1, $val2)
    {
        if ($val1 && $val2) {
            return min(array($val1, $val2));
        }
        if ($val1) {
            return $val1;
        }
        if ($val2) {
            return $val2;
        }

        return;
    }

    /**
     * Returns the number of bytes from php.ini parameter.
     *
     * @param $val
     *
     * @return int|string
     */
    private static function iniGetBytes($val)
    {
        $val = trim(ini_get($val));
        if ($val != '') {
            $last = strtolower(
                    $val{strlen($val) - 1}
            );
        } else {
            $last = '';
        }
        switch ($last) {
            // The 'G' modifier is available since PHP 5.1.0
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        return $val;
    }

    /**
     * Count number of element in array.
     *
     * @param mixed $item
     * @param mixed $key
     */
    private function countRecursive($item, $key)
    {
        ++$this->count;
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
     *
     * @throws SplashException
     */
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        // Check if there is a limit of input number in php
        // Throw exception if the limit is reached
        if (ini_get('max_input_vars') || ini_get('suhosin.get.max_vars')) {
            $maxGet = $this->getMinInConfiguration(ini_get('max_input_vars'), ini_get('suhosin.get.max_vars'));
            if ($maxGet !== null) {
                $this->count = 0;
                array_walk_recursive($_GET, array($this, 'countRecursive'));
                if ($this->count == $maxGet) {
                    if ($this->log != null) {
                        $this->log->error('Max input vars reaches for get parameters ({maxGet}). Check your variable max_input_vars in php.ini or suhosin module suhosin.get.max_vars.', ['maxGet' => $maxGet]);
                    }
                    throw new SplashException('Max input vars reaches for get parameters ('.$maxGet.'). Check your variable max_input_vars in php.ini or suhosin module suhosin.get.max_vars.');
                }
            }
        }
        if (ini_get('max_input_vars') || ini_get('suhosin.post.max_vars')) {
            $maxPost = $this->getMinInConfiguration(ini_get('max_input_vars'), ini_get('suhosin.post.max_vars'));
            if ($maxPost !== null) {
                $this->count = 0;
                array_walk_recursive($_POST, array($this, 'countRecursive'));
                if ($this->count == $maxPost) {
                    if ($this->log != null) {
                        $this->log->error('Max input vars reaches for post parameters ({maxPost}). Check your variable max_input_vars in php.ini or suhosin module suhosin.post.max_vars.', ['maxPost' => $maxPost]);
                    }
                    throw new SplashException('Max input vars reaches for post parameters ('.$maxPost.'). Check your variable max_input_vars in php.ini or suhosin module suhosin.post.max_vars.');
                }
            }
        }
        if (ini_get('max_input_vars') || ini_get('suhosin.request.max_vars')) {
            $maxRequest = $this->getMinInConfiguration(ini_get('max_input_vars'), ini_get('suhosin.request.max_vars'));
            if ($maxRequest !== null) {
                $this->count = 0;
                array_walk_recursive($_REQUEST, array($this, 'countRecursive'));
                if ($this->count == $maxRequest) {
                    if ($this->log != null) {
                        $this->log->error('Max input vars reaches for request parameters ({maxRequest}). Check your variable max_input_vars in php.ini or suhosin module suhosin.request.max_vars.', ['maxRequest' => $maxRequest]);
                    }
                    throw new SplashException('Max input vars reaches for request parameters ('.$maxRequest.'). Check your variable max_input_vars in php.ini or suhosin module suhosin.request.max_vars.');
                }
            }
        }
        if (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) == 'post' && empty($_POST) && empty($_FILES)) {
            $maxPostSize = self::iniGetBytes('post_max_size');
            if ($_SERVER['CONTENT_LENGTH'] > $maxPostSize) {
                if ($this->log != null) {
                    $this->log->error('Max post size exceeded! Got {length} bytes, but limit is {maxPostSize} bytes. Edit post_max_size setting in your php.ini.', ['length' => $_SERVER['CONTENT_LENGTH'], 'maxPostSize' => $maxPostSize]);
                }
                throw new SplashException(
                    sprintf('Max post size exceeded! Got %s bytes, but limit is %s bytes. Edit post_max_size setting in your php.ini.',
                        $_SERVER['CONTENT_LENGTH'],
                        $maxPostSize
                    )
                );
            }
        }

        //If no Exception has been thrown, call next router
        return $out($request, $response);
    }
}
