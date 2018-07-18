<?php

namespace TheCodingMachine\Splash\Store;

use TheCodingMachine\Splash\Services\SplashRouteInterface;
use TheCodingMachine\Splash\Utils\SplashException;
/*
 * A SplashUrlNode is a datastructure optimised to navigate all possible URLs known to the application.
 * A SplashUrlNode represents all possible routes starting at the current position (just after a / in a URL)
 *
 * @author David Negrier
 */
use TheCodingMachine\Splash\Services\SplashRoute;
use Psr\Http\Message\ServerRequestInterface;

class SplashUrlNode
{
    /**
     * An array of subnodes
     * The key is the string to be appended to the URL.
     *
     * @var array<string, SplashUrlNode>
     */
    private $children = array();

    /**
     * An array of parameterized subnodes.
     *
     * @var array<string, SplashUrlNode>
     */
    private $parameterizedChildren = array();

    /**
     * A list of callbacks (assicated to there HTTP method).
     *
     * @var array<string, SplashRouteInterface>
     */
    private $callbacks = array();

    /**
     * A list of callbacks (assicated to there HTTP method) finishing with "*".
     *
     * @var array<string, SplashRouteInterface>
     */
    private $wildcardCallbacks = array();

    public function registerCallback(SplashRouteInterface $callback)
    {
        $this->addUrl(explode('/', $callback->getUrl()), $callback);
    }

    /**
     * Registers a new URL.
     * The URL is passed as an array of strings (exploded on /).
     *
     * @param array<string> $urlParts
     */
    protected function addUrl(array $urlParts, SplashRouteInterface $callback)
    {
        if (!empty($urlParts)) {
            $key = array_shift($urlParts);

            if ($key == '*') {
                // Wildcard URL
                if (!empty($urlParts)) {
                    throw new SplashException('Sorry, the URL pattern /foo/*/bar is not supported. The wildcard (*) must be at the end of an URL');
                }

                $httpMethods = $callback->getHttpMethods();
                if (empty($httpMethods)) {
                    if (isset($this->wildcardCallbacks[''])) {
                        throw new SplashException("An error occured while looking at the list URL managed in Splash. The URL '".$callback->getUrl()."' is associated "
                                ."to 2 methods: \$".$callback->getControllerInstanceName().'->'.$callback->getMethodName()." and \$".$this->wildcardCallbacks['']->getControllerInstanceName().'->'.$this->wildcardCallbacks['']->getMethodName());
                    }
                    $this->wildcardCallbacks[''] = $callback;
                } else {
                    foreach ($httpMethods as $httpMethod) {
                        if (isset($this->wildcardCallbacks[$httpMethod])) {
                            throw new SplashException("An error occured while looking at the list URL managed in Splash. The URL '".$callback->getUrl()."' for HTTP method '".$httpMethod."' is associated "
                                    ."to 2 methods: \$".$callback->getControllerInstanceName().'->'.$callback->getMethodName()." and \$".$this->wildcardCallbacks[$httpMethod]->getControllerInstanceName().'->'.$this->wildcardCallbacks[$httpMethod]->getMethodName());
                        }
                        $this->wildcardCallbacks[$httpMethod] = $callback;
                    }
                }
            } elseif (strpos($key, '{') === 0 && strpos($key, '}') === strlen($key) - 1) {
                // Parameterized URL element
                $varName = substr($key, 1, strlen($key) - 2);

                if (!isset($this->parameterizedChildren[$varName])) {
                    $this->parameterizedChildren[$varName] = new self();
                }
                $this->parameterizedChildren[$varName]->addUrl($urlParts, $callback);
            } else {
                // Usual URL element
                if (!isset($this->children[$key])) {
                    $this->children[$key] = new self();
                }
                $this->children[$key]->addUrl($urlParts, $callback);
            }
        } else {
            $httpMethods = $callback->getHttpMethods();
            if (empty($httpMethods)) {
                if (isset($this->callbacks[''])) {
                    throw new SplashException("An error occured while looking at the list URL managed in Splash. The URL '".$callback->getUrl()."' is associated "
                        ."to 2 methods: \$".$callback->getControllerInstanceName().'->'.$callback->getMethodName()." and \$".$this->callbacks['']->getControllerInstanceName().'->'.$this->callbacks['']->getMethodName());
                }
                $this->callbacks[''] = $callback;
            } else {
                foreach ($httpMethods as $httpMethod) {
                    if (isset($this->callbacks[$httpMethod])) {
                        throw new SplashException("An error occured while looking at the list URL managed in Splash. The URL '".$callback->getUrl()."' for HTTP method '".$httpMethod."' is associated "
                            ."to 2 methods: \$".$callback->getControllerInstanceName().'->'.$callback->getMethodName()." and \$".$this->callbacks[$httpMethod]->getControllerInstanceName().'->'.$this->callbacks[$httpMethod]->getMethodName());
                    }
                    $this->callbacks[$httpMethod] = $callback;
                }
            }
        }
    }

    /**
     * Walks through the nodes to find the callback associated to the URL.
     *
     * @param string                 $url
     * @param ServerRequestInterface $request
     *
     * @return SplashRouteInterface
     */
    public function walk($url, ServerRequestInterface $request)
    {
        return $this->walkArray(explode('/', $url), $request, array());
    }

    /**
     * Walks through the nodes to find the callback associated to the URL.
     *
     * @param array                  $urlParts
     * @param ServerRequestInterface $request
     * @param array                  $parameters
     * @param SplashRouteInterface   $closestWildcardRoute The last wildcard (*) route encountered while navigating the tree.
     *
     * @return SplashRouteInterface
     *
     * @throws SplashException
     */
    private function walkArray(array $urlParts, ServerRequestInterface  $request, array $parameters, $closestWildcardRoute = null)
    {
        $httpMethod = $request->getMethod();

        if (isset($this->wildcardCallbacks[$httpMethod])) {
            $closestWildcardRoute = $this->wildcardCallbacks[$httpMethod];
            $closestWildcardRoute->setFilledParameters($parameters);
        } elseif (isset($this->wildcardCallbacks[''])) {
            $closestWildcardRoute = $this->wildcardCallbacks[''];
            $closestWildcardRoute->setFilledParameters($parameters);
        }

        if (!empty($urlParts)) {
            $key = array_shift($urlParts);
            if (isset($this->children[$key])) {
                return $this->children[$key]->walkArray($urlParts, $request, $parameters, $closestWildcardRoute);
            }

            foreach ($this->parameterizedChildren as $varName => $splashUrlNode) {
                if (isset($parameters[$varName])) {
                    throw new SplashException("An error occured while looking at the list URL managed in Splash. In a @URL annotation, the parameter '{$parameters[$varName]}' appears twice. That should never happen");
                }
                $newParams = $parameters;
                $newParams[$varName] = $key;
                $result = $this->parameterizedChildren[$varName]->walkArray($urlParts, $request, $newParams, $closestWildcardRoute);
                if ($result !== null) {
                    return $result;
                }
            }

            // If we arrive here, there was no parametrized URL matching our objective
            return $closestWildcardRoute;
        } else {
            if (isset($this->callbacks[$httpMethod])) {
                $route = $this->callbacks[$httpMethod];
                $route->setFilledParameters($parameters);

                return $route;
            } elseif (isset($this->callbacks[''])) {
                $route = $this->callbacks[''];
                $route->setFilledParameters($parameters);

                return $route;
            } else {
                return $closestWildcardRoute;
            }
        }
    }
}
