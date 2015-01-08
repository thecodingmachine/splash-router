<?php
namespace Mouf\Mvc\Splash;

use Symfony\Component\HttpFoundation\Response;
use Mouf\Html\HtmlElement\HtmlElementInterface;
use Mouf\Mvc\Splash\Utils\SplashException;

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
     * @param HtmlElementInterface $htmlElement    An HtmlElement to render.
     * @param int   $status  The response status code
     * @param array $headers An array of response headers
     */
    public function __construct(HtmlElementInterface $htmlElement = null, $status = 200, $headers = array())
    {
    	parent::__construct("", $status, $headers);
        $this->htmlElement = $htmlElement;
    }

    /**
     * {@inheritdoc}
     */
    public static function create($htmlElement = '', $status = 200, $headers = array())
    {
    	if (!$htmlElement instanceof HtmlElementInterface) {
    		throw new SplashException("HtmlResponse::create expects first argument to implement HtmlElementInterface");
    	}
        return new static($htmlElement, $status, $headers);
    }
    
    /**
     * Sets the HtmlElement to be rendered.
     * @param HtmlElementInterface $htmlElement
     */
    public function setHtmlElement(HtmlElementInterface $htmlElement) {
    	$this->htmlElement = $htmlElement;
    }
    
    /**
     * Returns the HtmlElement to be rendered.
     * @return \Mouf\Html\HtmlElement\HtmlElementInterface
     */
    public function getHtmlElement() {
    	return $this->htmlElement;
    }
    
    /**
     * Sends content for the current web response.
     *
     * @return Response
     */
    public function sendContent()
    {
    	if ($this->htmlElement) {
    		echo $this->htmlElement->toHtml();
    	}
    
    	return $this;
    }
}
