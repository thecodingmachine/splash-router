<?php

namespace TheCodingMachine\Splash\Services;

use TheCodingMachine\Splash\Utils\SplashException;

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
