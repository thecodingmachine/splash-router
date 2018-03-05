<?php

namespace Mouf\Mvc\Splash\Routers;

use Mouf\Mvc\Splash\Utils\SplashException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * This router :
 *  - just checks that some PHP settings are not exceeded : max_input_vars, max_post_size
 *  - doesn't actually 'routes' the request. It's more like a filter to me applied and check the request.
 *  - should be placed BEFORE the effective applications router and AFTER the Exceptions handling routers.
 *
 * @author Kevin Nguyen
 */
class PhpVarsCheckRouter implements MiddlewareInterface
{

    /**
     * A simple counter to check requests' length (GET, POST, REQUEST).
     *
     * @var int
     */
    private $count;

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

        return null;
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
        $val = (int) $val;
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
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param Request $request
     *
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws SplashException
     */
    public function process(Request $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Check if there is a limit of input number in php
        // Throw exception if the limit is reached
        if (ini_get('max_input_vars') || ini_get('suhosin.get.max_vars')) {
            $maxGet = $this->getMinInConfiguration(ini_get('max_input_vars'), ini_get('suhosin.get.max_vars'));
            if ($maxGet !== null) {
                $this->count = 0;
                array_walk_recursive($_GET, array($this, 'countRecursive'));
                if ($this->count === $maxGet) {
                    throw new SplashException('Max input vars reaches for get parameters ('.$maxGet.'). Check your variable max_input_vars in php.ini or suhosin module suhosin.get.max_vars.');
                }
            }
        }
        if (ini_get('max_input_vars') || ini_get('suhosin.post.max_vars')) {
            $maxPost = $this->getMinInConfiguration(ini_get('max_input_vars'), ini_get('suhosin.post.max_vars'));
            if ($maxPost !== null) {
                $this->count = 0;
                array_walk_recursive($_POST, array($this, 'countRecursive'));
                if ($this->count === $maxPost) {
                    throw new SplashException('Max input vars reaches for post parameters ('.$maxPost.'). Check your variable max_input_vars in php.ini or suhosin module suhosin.post.max_vars.');
                }
            }
        }
        if (ini_get('max_input_vars') || ini_get('suhosin.request.max_vars')) {
            $maxRequest = $this->getMinInConfiguration(ini_get('max_input_vars'), ini_get('suhosin.request.max_vars'));
            if ($maxRequest !== null) {
                $this->count = 0;
                array_walk_recursive($_REQUEST, array($this, 'countRecursive'));
                if ($this->count === $maxRequest) {
                    throw new SplashException('Max input vars reaches for request parameters ('.$maxRequest.'). Check your variable max_input_vars in php.ini or suhosin module suhosin.request.max_vars.');
                }
            }
        }
        if (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) == 'post' && empty($_POST) && empty($_FILES)) {
            $maxPostSize = self::iniGetBytes('post_max_size');
            if ($_SERVER['CONTENT_LENGTH'] > $maxPostSize) {
                throw new SplashException(
                    sprintf('Max post size exceeded! Got %s bytes, but limit is %s bytes. Edit post_max_size setting in your php.ini.',
                        $_SERVER['CONTENT_LENGTH'],
                        $maxPostSize
                    )
                );
            }
        }

        //If no Exception has been thrown, call next router
        return $handler->handle($request);
    }
}
