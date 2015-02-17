<?php
namespace Mouf\Mvc\Splash\Filters;

// FIXME: register filters another way
//FilterUtils::registerFilter("Admin");

class AdminAnnotation extends AbstractFilter
{

    /**
	 * Function to be called before the action.
	 */
    public function beforeAction()
    {
        UserService::checkAdmin();
    }

    /**
	 * Function to be called after the action.
	 */
    public function afterAction()
    {
    }
}
