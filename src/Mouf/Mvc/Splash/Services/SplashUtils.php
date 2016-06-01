<?php

namespace Mouf\Mvc\Splash\Services;

use Mouf\Mvc\Splash\Utils\SplashException;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\HtmlResponse;

class SplashUtils
{
    const MODE_WEAK = 'weak';
    const MODE_STRICT = 'strict';

    public static function buildControllerResponse($callback, $mode = self::MODE_STRICT, $debug = false)
    {
        ob_start();
        try {
            $result = $callback();
        } catch (\Exception $e) {
            ob_end_clean();
            // Rethrow and keep stack trace.
            throw $e;
        }
        $html = ob_get_clean();

        if (!empty($html) || $mode === self::MODE_WEAK) {
            if ($mode === self::MODE_WEAK) {
                $code = http_response_code();
                $headers = self::getResponseHeaders();

                // Suppress actual headers (re-add by PSR-7 Response)
                // If you don't remove old headers, it's duplicated in HTTP Headers
                foreach ($headers as $key => $head) {
                    header_remove($key);
                }

                if ($result !== null) {
                    // We might be in weak mode, it is not normal to have both an output and a response!
                    $html = '<h1>Output started in controller. It is not normal to have an output in the controller, and a response returned by the controller. Output detected:</h1>'.$html;
                    $code = 500;
                }

                return new HtmlResponse($html, $code, $headers);
            } else {
                if ($debug) {
                    $html = '<h1>Output started in controller. A controller should return an object implementing the ResponseInterface rather than outputting directly content. Output detected:</h1>'.$html;

                    return new HtmlResponse($html, 500);
                } else {
                    throw new SplashException('Output started in Controller : '.$html);
                }
            }
        }

        if (!$result instanceof ResponseInterface) {
            if ($result === null) {
                throw new SplashException('Your controller should return an instance of Psr\\Http\\Message\\ResponseInterface. Your controller did not return any value.');
            } else {
                $class = (gettype($result) == 'object') ? get_class($result) : gettype($result);
                throw new SplashException('Your controller should return an instance of Psr\\Http\\Message\\ResponseInterface. Type of value returned: '.$class);
            }
        }

        return $result;

        // TODO: If Symfony Response convert to psr-7
//        if ($result instanceof Response) {
//            if ($html !== "") {
//                throw new SplashException("You cannot output text AND return Response object in the same action. Output already started :'$html");
//            }
//
//            if (headers_sent()) {
//                $headers = headers_list();
//                throw new SplashException("Headers already sent. Detected headers are : ".var_export($headers, true));
//            }
//
//            return $result;
//        }
//
//        $code = http_response_code();
//        $headers = SplashUtils::greatResponseHeaders();
//
//        // Suppress actual headers (re-add by Symfony Response)
//        // If you don't remove old headers, it's duplicated in HTTP Headers
//        foreach ($headers as $key => $head) {
//            header_remove($key);
//        }
//
//        return new Response($html, $code, $headers);
    }

    /**
     * Same as apache_response_headers (for any server).
     *
     * @return array
     */
    private static function getResponseHeaders()
    {
        $arh = array();

        // headers_list don't return associative array
        $headers = headers_list();
        foreach ($headers as $header) {
            $header = explode(':', $header);
            $arh[array_shift($header)] = trim(implode(':', $header));
        }

        return $arh;
    }
}
