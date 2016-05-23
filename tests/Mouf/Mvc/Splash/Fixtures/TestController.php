<?php

namespace Mouf\Mvc\Splash\Fixtures;

use Psr\Http\Message\RequestInterface;
use Mouf\Mvc\Splash\Annotations\URL;

class TestController
{
    /**
     * @URL("/myurl")
     *
     * @param RequestInterface $request
     * @param string           $compulsory
     * @param int              $id
     */
    public function myAction(RequestInterface $request, $compulsory, $id = null)
    {
    }
}
