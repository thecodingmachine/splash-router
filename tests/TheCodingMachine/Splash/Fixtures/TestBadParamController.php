<?php

namespace TheCodingMachine\Splash\Fixtures;

use TheCodingMachine\Splash\Annotations\Action;
use TheCodingMachine\Splash\Annotations\Delete;
use TheCodingMachine\Splash\Annotations\Get;
use TheCodingMachine\Splash\Annotations\Post;
use TheCodingMachine\Splash\Annotations\Put;
use TheCodingMachine\Splash\Annotations\Title;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\JsonResponse;
use TheCodingMachine\Splash\Annotations\URL;

class TestBadParamController
{
    /**
     * @URL("/notexistparam/{$this->notexist}/")
     */
    public function badParam()
    {
        return new JsonResponse([
        ]);
    }
}
