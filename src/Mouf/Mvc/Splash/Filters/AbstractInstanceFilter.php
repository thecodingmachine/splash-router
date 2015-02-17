<?php
namespace Mouf\Mvc\Splash\Filters;

/**
 * Class to be extended to add a filter to an action.
 * Those filters, unlike AbsrtactFilters, must be instanciated as a Mouf instance.
 */
abstract class AbstractInstanceFilter extends AbstractFilter
{
    /**
	 * Sets the parameters stored in this annotation.
	 * This function is automatically called just after the annotation is created.
	 *
	 * @param string $value
	 */
    abstract public function setValue($value);
}
