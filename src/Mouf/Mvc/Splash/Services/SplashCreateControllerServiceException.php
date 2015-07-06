<?php

namespace Mouf\Mvc\Splash\Services;

use Mouf\Mvc\Splash\Utils\SplashException;

class SplashCreateControllerServiceException extends SplashException
{
    private $errors;

    /**
     * @return mixed
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param mixed $errors
     */
    public function setErrors($errors)
    {
        $this->errors = $errors;
    }
}
