<?php

namespace Mouf\Mvc\Splash\Services;

use Mouf\Annotations\URLAnnotation;
use Mouf\Mvc\Splash\Utils\SplashException;
use Mouf\Utils\Common\Validators\NumericValidator;
use Mouf\Reflection\MoufReflectionMethod;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;
use Mouf\Annotations\paramAnnotation;
use Mouf\Reflection\TypesDescriptor;
use Mouf\Reflection\MoufReflectionParameter;
use Symfony\Component\HttpFoundation\Request;

class SplashUtils
{
    /**
     * Analyses the method, the @param annotation parameters, and returns an array of SplashRequestParameterFetcher.
     *
     * @return array<SplashParameterFetcherInterface>
     */
    public static function mapParameters(MoufReflectionMethod $refMethod, URLAnnotation $urlAnnotation = null)
    {
        $parameters = $refMethod->getParameters();

        // Let's try to find parameters in the @URL annotation
        // Let's build a set of those parameters.

        if ($urlAnnotation != null) {
            $urlParamsList = array();
            /* @var $urlAnnotation URLAnnotation */
            $url = $urlAnnotation->getUrl();
            $urlParts = explode('/', $url);
            foreach ($urlParts as $part) {
                if (strpos($part, '{') === 0 && strpos($part, '}') === strlen($part) - 1) {
                    // Parameterized URL element
                    $varName = substr($part, 1, strlen($part) - 2);
                    $urlParamsList[$varName] = $varName;
                }
            }
        } else {
            $urlAnnotations = $refMethod->getAnnotations('URL');
            $urlParamsList = array();
            if ($urlAnnotations != null) {
                foreach ($urlAnnotations as $urlAnnotation) {
                    /* @var $urlAnnotation URLAnnotation */
                    $url = $urlAnnotation->getUrl();
                    $urlParts = explode('/', $url);
                    foreach ($urlParts as $part) {
                        if (strpos($part, '{') === 0 && strpos($part, '}') === strlen($part) - 1) {
                            // Parameterized URL element
                            $varName = substr($part, 1, strlen($part) - 2);
                            $urlParamsList[$varName] = $varName;
                        }
                    }
                }
            }
        }

        // Let's analyze the @param annotations.
        $paramAnnotations = $refMethod->getAnnotations('param');

        $values = array();
        foreach ($parameters as $parameter) {
            /* @var $parameter MoufReflectionParameter */

            // First step: let's see if there is an @param annotation for that parameter.
            $found = false;

            if ($parameter->getType() == 'Symfony\Component\HttpFoundation\Request') {
                $values[] = new SplashSymfonyRequestFetcher($parameter->getName());
                continue;
            }

            // Let's first see if our parameter is part of the URL
            if (isset($urlParamsList[$parameter->getName()])) {
                unset($urlParamsList[$parameter->getName()]);

                if ($parameter->isDefaultValueAvailable()) {
                    $value = new SplashUrlParameterFetcher($parameter->getName(), false, $parameter->getDefaultValue());
                } else {
                    $value = new SplashUrlParameterFetcher($parameter->getName(), true);
                }
                $values[] = $value;
                continue;
            }

            if ($paramAnnotations != null) {
                foreach ($paramAnnotations as $annotation) {
                    /* @var $annotation paramAnnotation */

                    if (substr($annotation->getParameterName(), 1) == $parameter->getName()) {
                        //$paramAnnotationAnalyzer = new ParamAnnotationAnalyzer($annotation);
                        //$value = $paramAnnotationAnalyzer->getValue();

                        if ($parameter->isDefaultValueAvailable()) {
                            $value = new SplashRequestParameterFetcher($parameter->getName(), false, $parameter->getDefaultValue());
                        } else {
                            $value = new SplashRequestParameterFetcher($parameter->getName(), true);
                        }
                        // FIXME! types is a TypesDescriptor! We should add a depdency on the validators
                        // Currently, there is none in composer.json!!!!!!!!!!!!!!!
                        // Then, we should add a "OrValidator" that validate one of the conditions.
                        // Note: a AndValidator might be cool to.
                        // FIXME
                        // FIXME
                        // FIXME
                        // FIXME
                        // FIXME
                        // FIXME
                        // FIXME
                        // FIXME
                        // FIXME
                        /*$type = strtolower($annotation->getTypes());
                        if ($type == "float" || $type == "double" || $type == "real" || $type == "number") {
                            $numericValidator = new NumericValidator();
                            $value->registerValidator($numericValidator);
                        } elseif ($type == "int" || $type == "integer") {
                            $intValidator = new NumericValidator();
                            $intValidator->allowDecimals = false;
                            $value->registerValidator($intValidator);
                        }*/
                        $values[] = $value;
                        $found = true;
                        break;
                    }
                }
            }

            if (!$found) {
                // There is no annotation for the parameter.
                // Let's map it to the request.

                if ($parameter->isDefaultValueAvailable()) {
                    $values[] = new SplashRequestParameterFetcher($parameter->getName(), false, $parameter->getDefaultValue());
                } else {
                    $values[] = new SplashRequestParameterFetcher($parameter->getName(), true);
                }
            }
        }

        if (!empty($urlParamsList)) {
            throw new SplashException("An error occured while handling a @URL annotation: the @URL annotation is parameterized with those variable(s): '".implode('/', $urlParamsList)."'. However, there is no such parameters in the function call.");
        }

        return $values;
    }

    public static function buildControllerResponse($callback)
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

        if (!empty($html)) {
            throw new SplashException('Output started in Controller : '.$html);
        }

        if (!$result instanceof ResponseInterface) {
            if ($result === null) {
                throw new SplashException('Your controller should return an instance of Psr\\Http\\Message\\ReponseInterface. Your controller did not return any value.');
            } else {
                $class = (gettype($result) == 'object') ? get_class($result) : gettype($result);
                throw new SplashException('Your controller should return an instance of Psr\\Http\\Message\\ReponseInterface. Type of value returned: '.$class);
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

    /*
     * Same as apache_response_headers (for any server)
     * @return array
     */
    /*private static function greatResponseHeaders() {
        $arh = array();

        // headers_list don't return associative array
        $headers = headers_list();
        foreach ($headers as $header) {
            $header = explode(":", $header);
            $arh[array_shift($header)] = trim(implode(":", $header));
        }
        return $arh;
    }*/
}
