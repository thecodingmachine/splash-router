<?php
use Mouf\MoufManager;
use Mouf\MoufUtils;

// Force loading this version of the class to bypass autoload
require_once "Mouf/Mvc/Splash/Services/SplashUrlManager.php";
require_once "Mouf/Mvc/Splash/Controllers/Admin/SplashViewUrlsController.php";
require_once "Mouf/Mvc/Splash/Controllers/Admin/SplashCreateControllerController.php";

MoufUtils::registerMainMenu('mvcMainMenu', 'MVC', null, 'mainMenu', 100);
MoufUtils::registerMenuItem('mvcSplashSubMenu', 'Splash', null, 'mvcMainMenu', 10);
MoufUtils::registerMenuItem('mvcSplashCreateControllerMenuItem', 'Create a new controller', 'splashCreateController/', 'mvcSplashSubMenu', 5);
MoufUtils::registerMenuItem('mvcSplashAdminUrlsListMenuItem', 'View URLs', 'splashViewUrls/', 'mvcSplashSubMenu', 10);

$moufManager = MoufManager::getMoufManager();
$moufManager->declareComponent('splashViewUrls', 'Mouf\\Mvc\\Splash\\Controllers\\Admin\\SplashViewUrlsController', true);
$moufManager->bindComponent('splashViewUrls', 'template', 'moufTemplate');
$moufManager->bindComponents('splashViewUrls', 'content', 'block.content');

$moufManager->declareComponent('splashCreateController', 'Mouf\\Mvc\\Splash\\Controllers\\Admin\\SplashCreateControllerController', true);
$moufManager->bindComponent('splashCreateController', 'template', 'moufTemplate');
$moufManager->bindComponents('splashCreateController', 'content', 'block.content');
