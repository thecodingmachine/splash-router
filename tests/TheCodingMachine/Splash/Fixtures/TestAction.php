<?php

namespace TheCodingMachine\Splash\Fixtures;

use TheCodingMachine\Splash\Annotations\Action;
use TheCodingMachine\Splash\Annotations\Delete;
use TheCodingMachine\Splash\Annotations\Get;
use TheCodingMachine\Splash\Annotations\Post;
use TheCodingMachine\Splash\Annotations\Put;
use TheCodingMachine\Splash\Annotations\Title;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;
use TheCodingMachine\Splash\Annotations\URL;

class TestAction
{
    /**
     * @Action
     */
    public function myAction()
    {
    }
}
