<?php

namespace Mouf\Annotations;

/**
 * An annotation used to allow a method of a controller to be accessible from the web.
 * Syntax: @URL your_url_goes_here.
 */
class URLAnnotation
{
    private $url;

    public function __construct($value)
    {
        $url = $value;
        $this->url = trim($url, " \t()\"'");
    }

    /**
     * Returns the URL.
     */
    public function getUrl()
    {
        return $this->url;
    }
}
