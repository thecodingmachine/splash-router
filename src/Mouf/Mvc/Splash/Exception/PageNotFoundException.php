<?php

namespace Mouf\Mvc\Splash\Exception;

/**
 * Throw this exception when a page is not found.
 * This will generate an HTTP 404 response.
 */
class PageNotFoundException extends \RuntimeException
{
    private $url;

    public static function create(string $url)
    {
        $exception = new self('Page not found: '.$url, 404);
        $exception->url = $url;

        return $exception;
    }

    /**
     * @return string
     */
    public function getUrl() : string
    {
        return $this->url;
    }
}
