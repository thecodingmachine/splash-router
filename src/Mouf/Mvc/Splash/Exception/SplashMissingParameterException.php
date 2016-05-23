<?php

namespace Mouf\Mvc\Splash\Exception;

/**
 * Exception thrown when a parameter should have been present in the query but is not available.
 *
 * @author David Negrier
 */
class SplashMissingParameterException extends BadRequestException
{
    public $key;

    public static function create($key)
    {
        $exception = new self('Missing parameter: '.$key, 400);
        $exception->key = $key;

        return $exception;
    }
}
