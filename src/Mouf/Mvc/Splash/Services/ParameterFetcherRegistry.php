<?php

namespace Mouf\Mvc\Splash\Services;

use Mouf\Mvc\Splash\Utils\SplashException;
use ReflectionMethod;

/**
 * A class that holds all parameter fetchers.
 */
class ParameterFetcherRegistry
{
    /**
     * @var ParameterFetcher[]
     */
    private $parameterFetchers;

    /**
     * @param ParameterFetcher[] $parameterFetchers
     */
    public function __construct(array $parameterFetchers = [])
    {
        $this->parameterFetchers = $parameterFetchers;
    }

    /**
     * Builds a registry with the default fetchers.
     *
     * @return ParameterFetcherRegistry
     */
    public static function buildDefaultControllerRegistry() : ParameterFetcherRegistry
    {
        return new self([
            new SplashRequestFetcher(),
            new SplashRequestParameterFetcher(),
        ]);
    }

    /**
     * Adds a parameter fetcher. It will be executed at the top of the list (first).
     *
     * @param ParameterFetcher $parameterFetcher
     *
     * @return ParameterFetcherRegistry
     */
    public function registerParameterFetcher(ParameterFetcher $parameterFetcher) : ParameterFetcherRegistry
    {
        array_unshift($this->parameterFetchers, $parameterFetcher);

        return $this;
    }

    /**
     * Analyses the method and returns an array of SplashRequestParameterFetcher.
     * Note: the return from this method is meant to be cached.
     *
     * @param ReflectionMethod $refMethod
     * @param string           $url
     *
     * @return array[] An array representing serializable fetchers. Each fetcher is represented as an array with 2 keys: "fetcherId" (an ID for the fetcher) and "data" (data required by the fetcher)
     *
     * @throws SplashException
     */
    public function mapParameters(ReflectionMethod $refMethod, string $url = null) : array
    {
        $parameters = $refMethod->getParameters();

        $values = [];

        foreach ($parameters as $parameter) {
            $found = false;
            foreach ($this->parameterFetchers as $id => $fetcher) {
                if ($fetcher->canHandle($parameter)) {
                    $data = $fetcher->getFetcherData($parameter, $url);
                    $values[] = ['fetcherId' => $id, 'data' => $data];
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                throw new SplashException('Unable to handle parameter $'.$parameter->getName().' in '.$parameter->getDeclaringClass()->getName().'::'.$parameter->getDeclaringFunction()->getName());
            }
        }

        return $values;
    }

    /**
     * Maps data returned by mapParameters to real arguments to be passed to the action.
     *
     * @param SplashRequestContext $context
     * @param array                $parametersMap
     *
     * @return array
     */
    public function toArguments(SplashRequestContext $context, array $parametersMap) : array
    {
        $arguments = [];
        foreach ($parametersMap as $parameter) {
            $fetcherid = $parameter['fetcherId'];
            $data = $parameter['data'];
            $arguments[] = $this->parameterFetchers[$fetcherid]->fetchValue($data, $context);
        }

        return $arguments;
    }
}
