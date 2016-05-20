<?php

namespace Mouf\Mvc\Splash\Controllers;

use Mouf\Mvc\Splash\Utils\SplashException;
use Mouf\Mvc\Splash\Services\SplashRoute;
use Mouf\Reflection\MoufReflectionClass;
use Mouf\MoufManager;
use Mouf\Mvc\Splash\Services\UrlProviderInterface;
use Mouf\Html\HtmlElement\Scopable;

/*require_once dirname(__FILE__)."/../views/404.php";
require_once dirname(__FILE__)."/../views/500.php";
require_once ROOT_PATH.'mouf/reflection/MoufReflectionClass.php';*/

abstract class Controller implements Scopable, UrlProviderInterface
{
    /**
     * Returns the default template used in Splash.
     * This can be configured in the "splash" instance.
     * Returns null if the "splash" instance does not exist.
     *
     * @return TemplateInterface
     */
    public static function getTemplate()
    {
        if (MoufManager::getMoufManager()->instanceExists('splash')) {
            $template = MoufManager::getMoufManager()->getInstance('splash')->defaultTemplate;

            return $template;
        } else {
            return;
        }
    }

    /**
     * Inludes the file (useful to load a view inside the Controllers scope).
     *
     * @param unknown_type $file
     */
    public function loadFile($file)
    {
        include $file;
    }

    /**
     * Returns an instance of the logger used by default in Splash.
     * This logger can be configured in the "splash" instance.
     * Note: in Drusplash, there is no such "splash" instance. Therefore, null will be returned.
     *
     * @return LogInterface
     */
    /*public static function getLogger() {
        if (MoufManager::getMoufManager()->instanceExists("splash")) {
            return MoufManager::getMoufManager()->getInstance("splash")->log;
        } else {
            return null;
        }
    }*/

    /**
     * Returns the list of URLs that can be accessed, and the function/method that should be called when the URL is called.
     *
     * @param string $instanceName The identifier for this object in the container.
     *
     * @return array <SplashRoute>
     *
     * @throws SplashException
     * @throws \Mouf\MoufException
     */
    public function getUrlsList($instanceName)
    {
        // TODOOOOOOOOOOOOOOOOOOOO migrate this OUT OF CONTROLLER!!!!
        // TODOOOOOOOOOOOOOOOOOOOO migrate this OUT OF CONTROLLER!!!!
        // TODOOOOOOOOOOOOOOOOOOOO migrate this OUT OF CONTROLLER!!!!
        // TODOOOOOOOOOOOOOOOOOOOO migrate this OUT OF CONTROLLER!!!!
        // TODOOOOOOOOOOOOOOOOOOOO migrate this OUT OF CONTROLLER!!!!
        // TODOOOOOOOOOOOOOOOOOOOO migrate this OUT OF CONTROLLER!!!!
        // TODOOOOOOOOOOOOOOOOOOOO migrate this OUT OF CONTROLLER!!!!
        // TODOOOOOOOOOOOOOOOOOOOO migrate this OUT OF CONTROLLER!!!!
    }
}
