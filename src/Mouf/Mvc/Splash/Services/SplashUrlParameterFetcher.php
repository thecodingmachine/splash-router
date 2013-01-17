<?php 
namespace Mouf\Mvc\Splash\Services;
use Mouf\Utils\Common\Validators\ValidatorInterface;

/**
 * This class fetches the parameter from the path of the URL.
 * 
 * @author David Negrier
 */
class SplashUrlParameterFetcher implements SplashParameterFetcherInterface {

	private $key;
	
	/**
	 * @var array<ValidatorInterface>
	 */
	private $validators = array();
	
	/**
	 * Constructor
	 * @param string $key The name of the parameter to fetch.
	 */
	public function __construct($key) {
		$this->key = $key;
	}
	
	/**
	 * Get the name of the parameter (only for error handling purposes).
	 *
	 * @return string
	 */
	public function getName() {
		return $this->key;
	}
	
	/**
	 * Adds a validator to the parameter fetcher.
	 * @param ValidatorInterface $validator
	 */
	public function registerValidator(ValidatorInterface $validator) {
		$this->validators[] = $validator;
	}
	
	/**
	 * We pass the context of the request, the object returns the value to fill.
	 * 
	 * @param SplashRequestContext $context
	 * @return mixed
	 */
	public function fetchValue(SplashRequestContext $context) {
		$request = $context->getUrlParameters();
		$value = $request[$this->key];
		foreach ($this->validators as $validator) {
			/* @var $validator ValidatorInterface */
			$result = $validator->doValidate($value);
			if (!$result) {
				throw new SplashValidationException($validator->getErrorMessage());
			}
		}
		return $value;
	}
}
?>