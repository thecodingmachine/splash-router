<?php

namespace Mouf\Mvc\Splash\Exception;

/**
 * Throw this exception when a bad request happens.
 * This will generate an HTTP 400 response.
 */
class BadRequestException extends \RuntimeException
{
}
