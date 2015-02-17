<?php
namespace Mouf\Mvc\Splash\Store;

use Mouf\Mvc\Splash\Utils\SplashException;

/**
 * A SplashUrlNode is a datastructure optimised to navigate all possible URLs known to the application.
 * A SplashUrlNode represents all possible routes starting at the current position (just after a / in a URL)
 *
 * @author David Negrier
 */
use Mouf\Mvc\Splash\Services\SplashRoute;
use Symfony\Component\HttpFoundation\Request;

class SplashUrlNode
{
    /**
	 * An array of subnodes
	 * The key is the string to be appended to the URL
	 *
	 * @var array<string, SplashUrlNode>
	 */
    private $children = array();

    /**
	 * An array of parameterized subnodes
	 *
	 * @var array<string, SplashUrlNode>
	 */
    private $parameterizedChildren = array();

    /**
	 * A list of callbacks (assicated to there HTTP method).
	 *
	 * @var array<string, SplashRoute>
	 */
    private $callbacks = array();

    /**
	 * A list of callbacks (assicated to there HTTP method) finishing with "*"
	 *
	 * @var array<string, SplashRoute>
	 */
    private $wildcardCallbacks = array();

    public function registerCallback(SplashRoute $callback)
    {
        $this->addUrl(explode("/", $callback->url), $callback);
    }

    /**
	 * Registers a new URL.
	 * The URL is passed as an array of strings (exploded on /)
	 *
	 * @param array<string> $urlParts
	 */
    protected function addUrl(array $urlParts, SplashRoute $callback)
    {
        if (!empty($urlParts)) {
            $key = array_shift($urlParts);

            if ($key=='*') {
                // Wildcard URL
                if (!empty($urlParts)) {
                    throw new SplashException("Sorry, the URL pattern /foo/*/bar is not supported. The wildcard (*) must be at the end of an URL");
                }

                if (empty($callback->httpMethods)) {
                    if (isset($this->wildcardCallbacks[""])) {
                        throw new SplashException("An error occured while looking at the list URL managed in Splash. The URL '".$callback->url."' is associated "
                                ."to 2 methods: \$".$callback->controllerInstanceName."->".$callback->methodName." and \$".$this->wildcardCallbacks[""]->controllerInstanceName."->".$this->wildcardCallbacks[""]->methodName);
                    }
                    $this->wildcardCallbacks[""] = $callback;
                } else {
                    foreach ($callback->httpMethods as $httpMethod) {
                        if (isset($this->wildcardCallbacks[$httpMethod])) {
                            throw new SplashException("An error occured while looking at the list URL managed in Splash. The URL '".$callback->url."' for HTTP method '".$httpMethod."' is associated "
                                    ."to 2 methods: \$".$callback->controllerInstanceName."->".$callback->methodName." and \$".$this->wildcardCallbacks[$httpMethod]->controllerInstanceName."->".$this->wildcardCallbacks[$httpMethod]->methodName);
                        }
                        $this->wildcardCallbacks[$httpMethod] = $callback;
                    }
                }
            } elseif (strpos($key, "{") === 0 && strpos($key, "}") === strlen($key)-1) {
                // Parameterized URL element
                $varName = substr($key, 1, strlen($key)-2);

                if (!isset($this->parameterizedChildren[$varName])) {
                    $this->parameterizedChildren[$varName] = new SplashUrlNode();
                }
                $this->parameterizedChildren[$varName]->addUrl($urlParts, $callback);
            } else {
                // Usual URL element
                if (!isset($this->children[$key])) {
                    $this->children[$key] = new SplashUrlNode();
                }
                $this->children[$key]->addUrl($urlParts, $callback);
            }
        } else {
            if (empty($callback->httpMethods)) {
                if (isset($this->callbacks[""])) {
                    throw new SplashException("An error occured while looking at the list URL managed in Splash. The URL '".$callback->url."' is associated "
                        ."to 2 methods: \$".$callback->controllerInstanceName."->".$callback->methodName." and \$".$this->callbacks[""]->controllerInstanceName."->".$this->callbacks[""]->methodName);
                }
                $this->callbacks[""] = $callback;
            } else {
                foreach ($callback->httpMethods as $httpMethod) {
                    if (isset($this->callbacks[$httpMethod])) {
                        throw new SplashException("An error occured while looking at the list URL managed in Splash. The URL '".$callback->url."' for HTTP method '".$httpMethod."' is associated "
                            ."to 2 methods: \$".$callback->controllerInstanceName."->".$callback->methodName." and \$".$this->callbacks[$httpMethod]->controllerInstanceName."->".$this->callbacks[$httpMethod]->methodName);
                    }
                    $this->callbacks[$httpMethod] = $callback;
                }
            }

        }
    }

    /**
	 * Walks through the nodes to find the callback associated to the URL
	 *
	 * @param string $url
	 * @param Request $request
	 * @return SplashRoute
	 */
    public function walk($url, Request $request)
    {
        return $this->walkArray(explode("/", $url), $request, array());
    }

    /**
	 * Walks through the nodes to find the callback associated to the URL
	 *
	 * @param array $urlParts
	 * @param Request $request
	 * @param array $parameters
	 * @param SplashRoute $closestWildcardRoute The last wildcard (*) route encountered while navigating the tree.
	 * @throws SplashException
	 * @return SplashRoute
	 */
    private function walkArray(array $urlParts, Request $request, array $parameters, $closestWildcardRoute = null)
    {
        $httpMethod = $request->getMethod();

        if (isset($this->wildcardCallbacks[$httpMethod])) {
            $closestWildcardRoute = $this->wildcardCallbacks[$httpMethod];
            $closestWildcardRoute->filledParameters = $parameters;
        } elseif (isset($this->wildcardCallbacks[""])) {
            $closestWildcardRoute = $this->wildcardCallbacks[""];
            $closestWildcardRoute->filledParameters = $parameters;
        }

        if (!empty($urlParts)) {
            $key = array_shift($urlParts);
            if (isset($this->children[$key])) {
                return $this->children[$key]->walkArray($urlParts, $request, $parameters,$closestWildcardRoute);
            } else {
                foreach ($this->parameterizedChildren as $varName=>$splashUrlNode) {
                    if (isset($parameters[$varName])) {
                        throw new SplashException("An error occured while looking at the list URL managed in Splash. In a @URL annotation, the parameter '$parameter' appears twice. That should never happen");
                    }
                    $newParams = $parameters;
                    $newParams[$varName] = $key;
                    $result = $this->parameterizedChildren[$varName]->walkArray($urlParts, $request, $newParams, $closestWildcardRoute);
                    if ($result != null) {
                        return $result;
                    }
                }
                // If we arrive here, there was no parameterized URL matching our objective
                return $closestWildcardRoute;
            }
        } else {
            if (isset($this->callbacks[$httpMethod])) {
                $route = $this->callbacks[$httpMethod];
                $route->filledParameters = $parameters;

                return $route;
            } elseif (isset($this->callbacks[""])) {
                $route = $this->callbacks[""];
                $route->filledParameters = $parameters;

                return $route;
            } else {
                return $closestWildcardRoute;
            }
        }

    }
}
