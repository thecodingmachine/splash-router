<?php

namespace Mouf\Mvc\Splash\Fixtures;

use Psr\Http\Message\RequestInterface;

class TestControllerDoubleTitle
{
    /**
     * @Action
     * @Title Main page
     * @Title And boom
     */
    public function index()
    {
    }
}
