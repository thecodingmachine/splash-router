<?php
namespace Mouf\Mvc\Splash\Fixtures;

use Psr\Http\Message\RequestInterface;

class TestController2
{
    private $param = 42;
    private $param2 = 52;

    /**
     * @URL /url/{$this->param}/foo/{$this->param2}
     */
    public function action1() {

    }

    /**
     * @Action
     */
    public function actionAnnotation() {

    }

    /**
     * @Action
     * @Title Main page
     * @Get
     * @Post
     * @Put
     * @Delete
     */
    public function index() {

    }
}