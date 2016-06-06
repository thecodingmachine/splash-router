<?php

use Mouf\MoufManager;
use Mouf\MoufUtils;

// Force loading this version of the class to bypass autoload
require_once 'Mouf/Mvc/Splash/Controllers/Admin/SplashCreateControllerController.php';

MoufUtils::registerMainMenu('mvcMainMenu', 'MVC', null, 'mainMenu', 100);
MoufUtils::registerMenuItem('mvcSplashSubMenu', 'Splash', null, 'mvcMainMenu', 10);
MoufUtils::registerMenuItem('mvcSplashCreateControllerMenuItem', 'Create a new controller', 'splashCreateController/', 'mvcSplashSubMenu', 5);

$moufManager = MoufManager::getMoufManager();
$moufManager->declareComponent('splashCreateController', 'Mouf\\Mvc\\Splash\\Controllers\\Admin\\SplashCreateControllerController', true);
$moufManager->bindComponent('splashCreateController', 'template', 'moufTemplate');
$moufManager->bindComponents('splashCreateController', 'content', 'block.content');
