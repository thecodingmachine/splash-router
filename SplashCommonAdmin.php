<?php
MoufUtils::registerMainMenu('mvcMainMenu', 'MVC', null, 'mainMenu', 100);
MoufUtils::registerMenuItem('mvcSplashSubMenu', 'Splash', null, 'mvcMainMenu', 10);
MoufUtils::registerMenuItem('mvcSplashAdminUrlsListMenuItem', 'View URLs', 'mouf/splashViewUrls/', 'mvcSplashSubMenu', 10);


MoufManager::getMoufManager()->declareComponent('splashViewUrls', 'SplashViewUrlsController', true);
MoufManager::getMoufManager()->bindComponent('splashViewUrls', 'template', 'moufTemplate');

?>