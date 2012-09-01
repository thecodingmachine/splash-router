<?php
namespace Mouf\Mvc\Splash\Filters;

/**
 * Class to be extended to add a filter to an action.
 */
abstract class AbstractFilter
{
	/**
	 * The controller this filter is applied to
	 * @var Controller
	 */
	protected $controller;

	/**
	 * The method this filter is applied to
	 * @var MoufReflectionMethod
	 */
	protected $refMethod;

    /*public function getAnnotationTarget()
    {
        return stubAnnotation::TARGET_METHOD+stubAnnotation::TARGET_CLASS;
    }*/

    /**
	 * Returns the priority. The higher the priority, the earlier the beforeAction will be executed and the later the afterAction will be executed.
	 * Default priority is 50.
	 * @return int The priority.
	 */
	protected function getPriority() {
		return 50;
	}


	/**
	 * Function to be called before the action.
	 */
	abstract public function beforeAction();

	/**
	 * Function to be called after the action.
	 */
	abstract public function afterAction();

	/**
	 * Sets the metadata for this annotation.
	 */
	public function setMetaData(Controller $controller, MoufReflectionMethod $refMethod) {
		$this->controller = $controller;
		$this->refMethod = $refMethod;
	}

	public static function compare($filter1, $filter2) {
		return ($filter1->getPriority() - $filter2->getPriority());
	}
}
?>