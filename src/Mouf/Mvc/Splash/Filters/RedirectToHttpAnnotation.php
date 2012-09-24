<?php
namespace Mouf\Mvc\Splash\Filters;

//FIXME: Provide filters another way...
//FilterUtils::registerFilter("RedirectToHttp");

/**
 * Filter that will bring the user back to HTTP if the user is in HTTPS.
 * The port can be specified in parameter if needed.
 * Works only with GET requests. If another request is performed, an exception will be thrown.
 */
class RedirectToHttpAnnotation extends AbstractFilter
{
	/**
	 * The value passed to the filter.
	 */
	protected $port;

	public function setValue($value) {
		$this->port = $value;
	}

	/**
	 * Function to be called before the action.
	 */
	public function beforeAction() {
		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) {
			if ($_SERVER['REQUEST_METHOD'] != 'GET') {
				throw new ApplicationException("annotation.redirecttohttp.getonly.title", "annotation.redirecttohttp.getonly.getonly.text");
			}
			header("Location: ".$this->selfURL());
			exit;
		}
	}

	/**
	 * Function to be called after the action.
	 */
	public function afterAction() {

	}

	private function selfURL() {
		$protocol = "http";
		$port = (empty($this->port)) ? "" : (":".$this->port);
		return $protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI'];
	}
	function strleft($s1, $s2) {
		return substr($s1, 0, strpos($s1, $s2));
	}
}
?>