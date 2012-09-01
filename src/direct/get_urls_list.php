<?php
/**
 * Returns a serialized string representing the array for all urls available along controller and method that it is bound to.
 */

if (!isset($_REQUEST["selfedit"]) || $_REQUEST["selfedit"]!="true") {
	require_once '../../../../../Mouf.php';
} else {
	require_once '../../../../MoufManager.php';
	MoufManager::initMoufManager();
	require_once '../../../../../MoufUniversalParameters.php';
	require_once '../../../../MoufAdmin.php';
}
require_once '../../../../../mouf/Moufspector.php';

// Note: checking rights is done after loading the required files because we need to open the session
// and only after can we check if it was not loaded before loading it ourselves...
require_once '../../../../../mouf/direct/utils/check_rights.php';


$moufManager = MoufManager::getMoufManager();
$instanceNames = $moufManager->findInstances("UrlProviderInterface");
		
$urls = array();

foreach ($instanceNames as $instanceName) {
	$urlProvider = $moufManager->getInstance($instanceName);
	/* @var $urlProvider UrlProviderInterface */
	$tmpUrlList = $urlProvider->getUrlsList();
	$urls = array_merge($urls, $tmpUrlList);
}

$encode = "php";
if (isset($_REQUEST["encode"]) && $_REQUEST["encode"]="json") {
	$encode = "json";
}

if ($encode == "php") {
	echo serialize($urls);
} elseif ($encode == "json") {
	echo json_encode($urls);
} else {
	echo "invalid encode parameter";
}
?>