<?php

namespace TheCodingMachine\Splash\Controllers;

use Mouf\Html\HtmlElement\Scopable;

/**
 * Legacy class required by controllers in Splash <8.
 *
 * @deprecated
 */
abstract class Controller implements Scopable
{
    /**
     * Inludes the file (useful to load a view inside the Controllers scope).
     *
     * @param string $file
     */
    public function loadFile($file)
    {
        include $file;
    }
}
