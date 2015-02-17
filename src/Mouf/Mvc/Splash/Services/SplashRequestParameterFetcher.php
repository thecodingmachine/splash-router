<?php
namespace Mouf\Mvc\Splash\Services;
use Mouf\Utils\Common\Validators\ValidatorInterface;

/**
 * This class fetches the parameter from the request.
 *
 * @author David Negrier
 */
class SplashRequestParameterFetcher implements SplashParameterFetcherInterface
{
    private $key;

    /**
	 * @var array<ValidatorInterface>
	 */
	private $validators = array();

	/**
	 * Whether the parameter is compulsory or not.
	 *
	 * @var bool
	 */
    private $compulsory;

    /**
	 * The default value for the parameter.
	 *
	 * @var mixed
	 */
    private $default;

    /**
	 * Constructor
	 * @param string $key The name of the parameter to fetch.
	 */
    public function __construct($key, $compulsory = true, $default = null)
    {
        $this->key = $key;
        $this->compulsory = $compulsory;
        $this->default = $default;
    }

    /**
	 * Get the name of the parameter (only for error handling purposes).
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->key;
	}

    /**
	 * Adds a validator to the parameter fetcher.
	 * @param ValidatorInterface $validator
	 */
	public function registerValidator(ValidatorInterface $validator)
	{
		$this->validators[] = $validator;
	}

	/**
	 * We pass the context of the request, the object returns the value to fill.
	 *
	 * @param SplashRequestContext $context
	 * @return mixed
	 */
    public function fetchValue(SplashRequestContext $context)
    {
        $request = $context->getRequest();
        $value = $request->get($this->key);
        if ($value !== null) {
            foreach ($this->validators as $validator) {
				/* @var $validator ValidatorInterface */
				$result = $validator->doValidate($value);
				if (!$result) {
					throw new SplashValidationException($validator->getErrorMessage());
				}
			}

            return $value;
        } elseif (!$this->compulsory) {
            return $this->default;
        } else {
            throw new SplashMissingParameterException($this->key);
        }
    }
}
