<?php
// First, let's request the install utilities
require_once '../../../../mouf/actions/InstallUtils.php';

// Let's init Mouf
InstallUtils::init(InstallUtils::$INIT_APP);

// Let's create the instance
$moufManager = MoufManager::getMoufManager();
if (!$moufManager->instanceExists("splashTranslateService")) {
	$moufManager->declareComponent("splashTranslateService", "FinePHPArrayTranslationService");
	$moufManager->setParameter("splashTranslateService", "i18nMessagePath", "plugins/mvc/splash-common/3.3/resources/");
	
	if (!$moufManager->instanceExists("splashBrowserLanguageDetection")) {
		$moufManager->declareComponent("splashBrowserLanguageDetection", "BrowserLanguageDetection");
	}
	
	$moufManager->bindComponentsViaSetter("splashTranslateService", "setLanguageDetection", "splashBrowserLanguageDetection");
}

// Let's rewrite the MoufComponents.php file to save the component
$moufManager->rewriteMouf();

// Finally, let's continue the install
InstallUtils::continueInstall();
?>