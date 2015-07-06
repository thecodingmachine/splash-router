<?php

namespace Mouf\Mvc\Splash\Controllers;

interface WebServiceInterface
{
    /**
     * Returns the URI of the Webservice.
     *
     * @return string
     */
    public function getWebserviceUri();
}
