<?php

namespace TheCodingMachine\Splash\Fixtures;

use Laminas\Diactoros\Response\HtmlResponse;
use TheCodingMachine\Splash\Annotations\URL;

/**
 * @TestFilter(id=24, foo="bar")
 */
class TestFilteredController
{
    /**
     * @URL("/foo")
     * @TestFilter(id=42)
     */
    public function index($id, $foo)
    {
        return new HtmlResponse((string) $id.$foo);
    }
}
