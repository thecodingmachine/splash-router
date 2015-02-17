<?php
namespace Mouf\Mvc\Splash\Services;

/**
 * Exception thrown when a paramter should have been present in the query but is not available.
 *
 * @author David Negrier
 */
class SplashMissingParameterException extends \Exception
{
    public $key;

    public function __construct($key)
    {
        parent::__construct("Missing parameter: ".$key);
        $this->key = $key;
    }

}
