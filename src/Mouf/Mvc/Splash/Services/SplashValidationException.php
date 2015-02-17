<?php
namespace Mouf\Mvc\Splash\Services;

/**
 * Exception thrown when a parameter does not validate.
 *
 * @author David Negrier
 */
class SplashValidationException extends \Exception
{
    public function setPrependedMessage($prependedMessage)
    {
        $this->message = $prependedMessage.$this->message;
    }
}
