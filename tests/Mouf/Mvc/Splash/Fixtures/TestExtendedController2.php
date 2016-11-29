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

class TestExtendedController2 extends TestController2
{
    // This empty controller is used to test access to {$this->param1} params when extending a class.
}
