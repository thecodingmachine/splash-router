<?php

namespace Mouf\Mvc\Splash\Controllers;

/**
 * This class should be extended by any Splash Web service.
 * Any method in this class will be directly accessible through the webservice.
 *
 * Warning! ANY method, not only method with @Action will be accessible!
 *
 * @author David
 */
abstract class WebService implements WebServiceInterface
{
    /**
     * The URI for the webservice.
     *
     * @Property
     * @Compulsory
     *
     * @var string
     */
    public $webserviceUri = 'urn://www.example.com/example';

    public function getWebserviceUri()
    {
        return $this->webserviceUri;
    }

    /**
     * Executes the action passed in parameter.
     *
     * @param string $method The method name to be called.
     */
    public function callAction($method)
    {
        // Actually, let's ignore the action.

        // Ok, is this method an action?
        $refClass = new MoufReflectionClass(get_class($this));

        $actions = array();

        foreach ($refClass->getMethods() as $method) {
            /* @var $method MoufReflectionMethod */
            if ($method->hasAnnotation('Action')) {
                $actions[] = $method;
            }
        }

        $server = new SoapServer(null, array('uri' => $this->webserviceUri));
        $server->addFunction('hello');
        $server->handle();

        if (method_exists($this, $method)) {
            $refMethod = $refClass->getMethod($method);    // $refMethod is an instance of stubReflectionMethod
            //$this->getLogger()->trace("REF METHOD : ".$refMethod." // has annotation Action ? ".$refMethod->hasAnnotation('Action'));
            if ($refMethod->hasAnnotation('Action') == false) {
                $debug = MoufManager::getMoufManager()->getInstance('splash')->debugMode;
                // This is not an action. Let's go in error.
                // FIXME
                self::FourOFour(iMsg('controller.404.no.action', get_class($this), $method), $debug);
                exit;
            }

            try {
                $filters = FilterUtils::getFilters($refMethod, $this);
                $this->beforeActionExecute($filters);

                // Ok, now, let's analyse the parameters.
                $argsArray = $this->mapParameters($refMethod);

                //call_user_func_array(array($this,$method), AdminBag::getInstance()->argsArray);
                call_user_func_array(array($this, $method), $argsArray);

                $this->afterActionExecute($filters);
            } catch (Exception $e) {
                $this->handleException($e);
            }
        } else {
            // "Method Not Found";
            $debug = MoufManager::getMoufManager()->getInstance('splash')->debugMode;
            self::FourOFour('404.wrong.method', $debug);
            exit;
        }
    }
}
