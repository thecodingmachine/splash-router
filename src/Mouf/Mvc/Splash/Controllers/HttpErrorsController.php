<?php

namespace Mouf\Mvc\Splash\Controllers;

use Mouf\Html\HtmlElement\Scopable;
use Mouf\Html\HtmlElement\HtmlBlock;
use Mouf\Html\Template\TemplateInterface;
use Mouf\Html\HtmlElement\HtmlElementInterface;
use Mouf\Mvc\Splash\Exception\BadRequestException;
use Mouf\Mvc\Splash\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

/**
 * This class provides the default Splash behaviour when a HTTP 404 and HTTP 500 error is triggered.
 * Fill free to configure/override/replace this controller with your own if you want to provide
 * a customized HTTP 400/404/500 page.
 *
 * @author David Négrier
 */
class HttpErrorsController implements Http400HandlerInterface, Http404HandlerInterface, Http500HandlerInterface, Scopable
{
    /**
     * The template used by Splash for displaying error pages (HTTP 400, 404 and 500).
     *
     * @var TemplateInterface
     */
    private $template;

    /**
     * The content block the template will be written into.
     *
     * @var HtmlBlock
     */
    private $contentBlock;

    /**
     * Whether we should display exception stacktrace or not in HTTP 500.
     *
     * @var bool
     */
    private $debugMode = true;

    /**
     * Content block displayed in case of a 400 error.
     * If not set, a default block will be used instead.
     *
     * @var HtmlElementInterface
     */
    protected $contentFor400;

    /**
     * Content block displayed in case of a 404 error.
     * If not set, a default block will be used instead.
     *
     * @var HtmlElementInterface
     */
    protected $contentFor404;

    /**
     * Content block displayed in case of a 500 error.
     * If not set, a default block will be used instead.
     *
     * @var HtmlElementInterface
     */
    protected $contentFor500;

    protected $exception;

    /**
     * @param TemplateInterface $template     The template used by Splash for displaying error pages (HTTP 400, 404 and 500).
     * @param HtmlBlock         $contentBlock The content block the template will be written into.
     * @param bool              $debugMode    Whether we should display exception stacktrace or not in HTTP 500.
     */
    public function __construct(TemplateInterface $template, HtmlBlock $contentBlock, bool $debugMode = true)
    {
        $this->template = $template;
        $this->contentBlock = $contentBlock;
        $this->debugMode = $debugMode;
    }

    /**
     * Creates a default controller.
     *
     * @param bool $debugMode
     *
     * @return HttpErrorsController
     */
    public static function createDefault(bool $debugMode = true)
    {
        $block = new HtmlBlock();
        $template = new DefaultSplashTemplate($block);

        return new self($template, $block, $debugMode);
    }

    /**
     * This function is called when a HTTP 400 error is triggered by the user.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function badRequest(BadRequestException $exception, ServerRequestInterface $request) : ResponseInterface
    {
        $this->exception = $exception;

        $acceptType = $request->getHeader('Accept');
        if (is_array($acceptType) && count($acceptType) > 0 && strpos($acceptType[0], 'json') !== false) {
            return new JsonResponse(['error' => ['message' => 'Bad request sent', 'type' => 'bad_request']], 400);
        }

        if ($this->contentFor400) {
            $this->contentBlock = $this->contentFor400;
        } else {
            $this->contentBlock->addFile(__DIR__.'/../../../../views/400.php', $this);
        }

        return HtmlResponse::create($this->template, 400);
    }

    /**
     * This function is called when a HTTP 404 error is triggered by the user.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function pageNotFound(ServerRequestInterface $request) : ResponseInterface
    {
        $acceptType = $request->getHeader('Accept');
        if (is_array($acceptType) && count($acceptType) > 0 && strpos($acceptType[0], 'json') !== false) {
            return new JsonResponse(['error' => ['message' => 'Page not found', 'type' => 'page_not_found']], 404);
        }

        if ($this->contentFor404) {
            $this->contentBlock->addHtmlElement($this->contentFor404);
        } else {
            $this->contentBlock->addFile(__DIR__.'/../../../../views/404.php', $this);
        }

        return HtmlResponse::create($this->template, 404);
    }

    /**
     * (non-PHPdoc).
     *
     * @see Mouf\Mvc\Splash\Controllers.Http500HandlerInterface::serverError()
     */
    public function serverError(\Throwable $exception, ServerRequestInterface $request) : ResponseInterface
    {
        $this->exception = $exception;

        $acceptType = $request->getHeader('Accept');
        if (is_array($acceptType) && count($acceptType) > 0 && strpos($acceptType[0], 'json') !== false) {
            return new JsonResponse(['error' => ['message' => $exception->getMessage(), 'type' => 'Exception', 'trace' => $this->debugMode ? $exception->getTraceAsString() : '']], 500);
        }

        if ($this->contentFor500) {
            $this->contentBlock->addHtmlElement($this->contentFor500);
        } else {
            $this->contentBlock->addFile(__DIR__.'/../../../../views/500.php', $this);
        }

        return HtmlResponse::create($this->template, 500);
    }

    /**
     * Includes the file (useful to load a view inside the Controllers scope).
     *
     * @param string $file
     */
    public function loadFile($file)
    {
        include $file;
    }

    /**
     * Content block displayed in case of a 400 error.
     * If not set, a default block will be used instead.
     *
     * @param HtmlElementInterface $contentFor400
     *
     * @return $this
     */
    public function setContentFor400(HtmlElementInterface $contentFor400)
    {
        $this->contentFor400 = $contentFor400;

        return $this;
    }

    /**
     * Content block displayed in case of a 404 error.
     * If not set, a default block will be used instead.
     *
     * @param HtmlElementInterface $contentFor404
     *
     * @return $this
     */
    public function setContentFor404(HtmlElementInterface $contentFor404)
    {
        $this->contentFor404 = $contentFor404;

        return $this;
    }

    /**
     * Content block displayed in case of a 500 error.
     * If not set, a default block will be used instead.
     *
     * @param HtmlElementInterface $contentFor500
     *
     * @return $this
     */
    public function setContentFor500(HtmlElementInterface $contentFor500)
    {
        $this->contentFor500 = $contentFor500;

        return $this;
    }
}
