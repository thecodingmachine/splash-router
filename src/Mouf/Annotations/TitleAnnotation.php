<?php

namespace Mouf\Annotations;

/**
 * An annotation used to specify the Title of an action or an URL.
 * Syntax: @Title My Page Title.
 */
class TitleAnnotation
{
    private $title;

    public function __construct($value)
    {
        $url = $value;
        $this->title = trim($url, " \t()\"'");
    }

    /**
     * Returns the URL.
     */
    public function getTitle()
    {
        return $this->title;
    }
}
