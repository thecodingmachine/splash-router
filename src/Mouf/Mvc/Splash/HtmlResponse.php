<?php

namespace Mouf\Mvc\Splash;

use Mouf\Html\HtmlElement\HtmlElementInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

/**
 * This class is a Symfony 2 response that takes in parameter a HtmlElementInterface element and will render it.
 *
 * @author David Négrier <david@mouf-php.com>
 */
class HtmlResponse extends Response
{
    /**
     * @var HtmlElementInterface
     */
    protected $htmlElement;

    /**
     * Constructor.
     *
     * @param HtmlElementInterface $htmlElement An HtmlElement to render.
     * @param int                  $status      The response status code
     * @param array                $headers     An array of response headers
     */
    public function __construct(HtmlElementInterface $htmlElement, $status = 200, $headers = array())
    {
        parent::__construct('php://temp', $status, $headers);

        $this->htmlElement = $htmlElement;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(HtmlElementInterface $htmlElement, $status = 200, $headers = array())
    {
        return new static($htmlElement, $status, $headers);
    }

    /**
     * Sets the HtmlElement to be rendered.
     *
     * @param HtmlElementInterface $htmlElement
     */
    public function setHtmlElement(HtmlElementInterface $htmlElement)
    {
        $this->htmlElement = $htmlElement;
    }

    /**
     * Returns the HtmlElement to be rendered.
     *
     * @return \Mouf\Html\HtmlElement\HtmlElementInterface
     */
    public function getHtmlElement()
    {
        return $this->htmlElement;
    }

    /**
     * Gets the body of the message.
     *
     * @return StreamInterface Returns the body as a stream.
     */
    public function getBody()
    {
        ob_start();
        $this->htmlElement->toHtml();
        $content = ob_get_clean();
        $stream = new Stream('php://memory', 'wb+');
        $stream->write($content);

        return $stream;
    }
}
