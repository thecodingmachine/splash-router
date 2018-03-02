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

class TestController2
{
    private $param = 42;
    private $param2 = 52;

    /**
     * @URL("/url/{$this->param}/foo/{$this->param2}")
     */
    public function action1()
    {
        return new JsonResponse([
            "hello" => "world"
        ]);
    }

    /**
     * @Action
     */
    public function actionAnnotation()
    {
    }

    /**
     * @Action
     * @Title("Main page")
     * @Get
     * @Post
     * @Put
     * @Delete
     */
    public function index()
    {
    }

    /**
     * @URL("/foo/{var}/bar")
     */
    public function completeTest($id, ServerRequestInterface $request, $var, $opt = 42)
    {
        return new JsonResponse([
           'id' => $id,
            'id2' => $request->getQueryParams()['id'],
            'var' => $var,
            'opt' => $opt,
        ]);
    }

    /**
     * @Action
     */
    public function triggerException()
    {
        throw new \Exception('boum!');
    }
}
