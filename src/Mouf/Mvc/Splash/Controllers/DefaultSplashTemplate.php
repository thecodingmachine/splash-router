<?php

namespace Mouf\Mvc\Splash\Controllers;

use Mouf\Html\HtmlElement\HtmlBlock;
use Mouf\Html\Template\BaseTemplate\BaseTemplate;

class DefaultSplashTemplate extends BaseTemplate
{
    public function __construct(HtmlBlock $content)
    {
        parent::__construct();
        $this->setContent($content);
    }

    /**
     * Renders the object in HTML.
     * The Html is echoed directly into the output.
     */
    public function toHtml()
    {
        header('Content-Type: text/html; charset=utf-8');

        include __DIR__.'/../../../../views/template.php';
    }
}
