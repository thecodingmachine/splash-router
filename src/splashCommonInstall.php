<?php
use Mouf\MoufManager;

use Mouf\Actions\InstallUtils;

// First, let's request the install utilities
require_once __DIR__."/../../../autoload.php";

// Let's init Mouf
InstallUtils::init(InstallUtils::$INIT_APP);

// Let's create the instance
$moufManager = MoufManager::getMoufManager();
if (!$moufManager->instanceExists("splashTranslateService")) {
	$splashTranslateService = $moufManager->createInstance("Mouf\\Utils\\I18n\\Fine\\Translate\\FinePHPArrayTranslationService");
	$splashTranslateService->setName("splashTranslateService");
	$splashTranslateService->getProperty("i18nMessagePath")->setValue("vendor/mouf/mvc.splash-common/resources/");
	
	if (!$moufManager->instanceExists("splashBrowserLanguageDetection")) {
		$splashBrowserLanguageDetection = $moufManager->createInstance("Mouf\\Utils\\I18n\\Fine\\Language\\BrowserLanguageDetection");
		$splashBrowserLanguageDetection->setName("splashBrowserLanguageDetection");
	}
	
	$splashTranslateService->getProperty("languageDetection")->setValue($splashBrowserLanguageDetection);
}

// Let's rewrite the MoufComponents.php file to save the component
$moufManager->rewriteMouf();

// Finally, let's continue the install
InstallUtils::continueInstall(isset($_REQUEST['selfedit']) && $_REQUEST['selfedit'] == 'true');
?>