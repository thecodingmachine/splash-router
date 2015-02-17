<?php
namespace Mouf\Mvc\Splash\Routers;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;

/**
 * This router :
 *  - just checks that some PHP settings are not exeeded : max_input_vars, max_post_size
 *  - doesn't actually 'routes' the request. It's more like a filter to me applied and check the request.
 *  - should be placed BEFORE the effective applications router and AFTER the Exceptions handling routers
 *
 * @author Kevin Nguyen
 */
class PhpVarsCheckRouter implements HttpKernelInterface
{
    /**
	 * The logger used by Splash
	 *
	 * @var LoggerInterface
	 */
    private $log;

    /**
	 * The router that will handle the request if this one has not thrown any Exception
	 *
	 * @var HttpKernelInterface
	 */
    private $fallBackRouter;

    /**
	 * A simple counter to check requests' length (GET, POST, REQUEST)
	 *
	 * @var int
	 */
    private $count;

    /**
	 * @Important
	 * @param HttpKernelInterface $fallBackRouter Router used if no page is found for this controller.
	 * @param LoggerInterface $log The logger used by Splash
	 */
    public function __construct(HttpKernelInterface $fallBackRouter, LoggerInterface $log = null)
    {
        $this->fallBackRouter = $fallBackRouter;
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
        // Check if there is a limit of input number in php
        // Throw exception if the limit is reached
        if (ini_get('max_input_vars') || ini_get('suhosin.get.max_vars')) {
            $maxGet = $this->getMinInConfiguration(ini_get('max_input_vars'), ini_get('suhosin.get.max_vars'));
            if ($maxGet !== null) {
                $this->count = 0;
                array_walk_recursive($_GET, array($this, 'countRecursive'));
                if ($this->count == $maxGet) {
                    if ($this->log != null) {
                        $this->log->error('Max input vars reaches for get parameters ({maxGet}). Check your variable max_input_vars in php.ini or suhosin module suhosin.get.max_vars.', ["maxGet" => $maxGet]);
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
                        $this->log->error('Max input vars reaches for post parameters ({maxPost}). Check your variable max_input_vars in php.ini or suhosin module suhosin.post.max_vars.', ["maxPost" => $maxPost]);
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
                        $this->log->error('Max input vars reaches for request parameters ({maxRequest}). Check your variable max_input_vars in php.ini or suhosin module suhosin.request.max_vars.', ["maxRequest" => $maxRequest]);
                    }
                    throw new SplashException('Max input vars reaches for request parameters ('.$maxRequest.'). Check your variable max_input_vars in php.ini or suhosin module suhosin.request.max_vars.');
                }
            }
        }
        if (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) == 'post' && empty($_POST) && empty($_FILES)) {
            $maxPostSize = self::iniGetBytes('post_max_size');
            if ($_SERVER['CONTENT_LENGTH'] > $maxPostSize) {
                if ($this->log != null) {
                    $this->log->error('Max post size exceeded! Got {length} bytes, but limit is {maxPostSize} bytes. Edit post_max_size setting in your php.ini.', ["length" => $_SERVER['CONTENT_LENGTH'], "maxPostSize" => $maxPostSize]);
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
        return $this->fallBackRouter->handle($request, $type, $catch);
    }

    /**
	 * Get the min in 2 values if there exist
	 * @param int $val1
	 * @param int $val2
	 * @return int|NULL
	 */
    private function getMinInConfiguration($val1, $val2)
    {
        if($val1 && $val2)

            return min(array($val1, $val2));
        if($val1)

            return $val1;
        if($val2)

            return $val2;
        return null;
    }

    /**
	 * Returns the number of bytes from php.ini parameter
	 *
	 * @param $val
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
	 * Count number of element in array
	 * @param mixed $item
	 * @param mixed $key
	 */
    private function countRecursive($item, $key)
    {
        $this->count ++;
    }
}
