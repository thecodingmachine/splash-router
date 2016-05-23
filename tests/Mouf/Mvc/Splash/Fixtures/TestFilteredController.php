<?php

namespace Mouf\Mvc\Splash\Fixtures;

use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;
use Mouf\Mvc\Splash\Annotations\URL;

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
