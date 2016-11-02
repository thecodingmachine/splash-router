<?php

namespace Mouf\Mvc\Splash\Fixtures;

use Mouf\Mvc\Splash\Annotations\Action;
use Mouf\Mvc\Splash\Annotations\Delete;
use Mouf\Mvc\Splash\Annotations\Get;
use Mouf\Mvc\Splash\Annotations\Post;
use Mouf\Mvc\Splash\Annotations\Put;
use Mouf\Mvc\Splash\Annotations\Title;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;
use Mouf\Mvc\Splash\Annotations\URL;

class TestController3
{

    /**
     * @URL("/🍕")
     */
    public function action1()
    {
        return new JsonResponse([
           'type' => 'success'
        ]);
    }
}
