<?php

namespace Mouf\Mvc\Splash\Controllers;

use Mouf\Html\HtmlElement\Scopable;

/**
 * Legacy class required by controllers in Splash <8.
 */
abstract class Controller implements Scopable
{
    /**
     * Inludes the file (useful to load a view inside the Controllers scope).
     *
     * @param unknown_type $file
     */
    public function loadFile($file)
    {
        include $file;
    }
}
