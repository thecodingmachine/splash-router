<?php
use Mouf\MoufManager;
use Mouf\MoufUtils;

// Force loading this version of the class to bypass autoload
require_once "Mouf/Mvc/Splash/Services/SplashUrlManager.php";
require_once "Mouf/Mvc/Splash/Controllers/Admin/SplashViewUrlsController.php";

MoufUtils::registerMainMenu('mvcMainMenu', 'MVC', null, 'mainMenu', 100);
MoufUtils::registerMenuItem('mvcSplashSubMenu', 'Splash', null, 'mvcMainMenu', 10);
MoufUtils::registerMenuItem('mvcSplashAdminUrlsListMenuItem', 'View URLs', 'splashViewUrls/', 'mvcSplashSubMenu', 10);

$moufManager = MoufManager::getMoufManager();
$moufManager->declareComponent('splashViewUrls', 'Mouf\\Mvc\\Splash\\Controllers\\Admin\\SplashViewUrlsController', true);
$moufManager->bindComponent('splashViewUrls', 'template', 'moufTemplate');
$moufManager->bindComponents('splashViewUrls', 'content', 'block.content');

?>