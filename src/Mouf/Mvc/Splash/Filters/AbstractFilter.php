<?php
namespace Mouf\Mvc\Splash\Filters;

use Mouf\Reflection\MoufReflectionMethod;

use Mouf\Mvc\Splash\Controllers\Controller;


/**
 * Class to be extended to add a filter to an action.
 */
abstract class AbstractFilter
{
	/**
	 * The controller this filter is applied to
	 * @var Controller
	 */
	private $controller;

	/**
	 * The method this filter is applied to
	 * @var MoufReflectionMethod
	 */
	private $refMethod;

    /**
     * Returns the controller this filter is applied to
     * @return Controller
     */
    protected function getController() {
        if (!$this->controller) {
            $moufManager = \Mouf\MoufManager::getMoufManager();
            $this->controller = $moufManager->getInstance($this->serializeInstanceName);
        }
        return $this->controller;
    }

    /**
     * Returns method this filter is applied to
     * @return MoufReflectionMethod
     */
    protected function getRefMethod() {
        if (!$this->refMethod) {
            $moufManager = \Mouf\MoufManager::getMoufManager();
            $this->refMethod = new MoufReflectionMethod(new \Mouf\Reflection\MoufReflectionClass(get_class($this->controller)), $this->serializeMethodName);
        }
        return $this->refMethod;
    }


	
	/**
	 * Used in serialization
	 * @var string
	 */
	protected $serializeInstanceName;

	/**
	 * Used in serialization
	 * @var string
	 */
	protected $serializeMethodName;

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
        
        /**
        * TODO: se demander est-ce que les pointeurs sont nécessaires ou pas. Dans l'idéal, la classe pourrait ne pas contenir de pointeurs.
        * @return array
        */
		public function __sleep() {
			$objectVars = get_object_vars($this);
			unset($objectVars['controller']);
			unset($objectVars['refMethod']);
			
			$moufManager = \Mouf\MoufManager::getMoufManager();
			$this->serializeInstanceName = $moufManager->findInstanceName($this->controller);
			$this->serializeMethodName = $this->refMethod->getName();
			return array_keys($objectVars);
       	}

       public function __wakeup() {
           /*$moufManager = \Mouf\MoufManager::getMoufManager();

           if($moufManager->has($this->serializeInstanceName)){
               $this->controller = $moufManager->getInstance($this->serializeInstanceName);
               $this->refMethod = new MoufReflectionMethod(new \Mouf\Reflection\MoufReflectionClass(get_class($this->controller)), $this->serializeMethodName);
           }*/
       }
}
?>