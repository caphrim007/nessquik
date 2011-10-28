<?php

if (!defined("_ABSPATH")) {
	define("_ABSPATH", dirname(dirname(dirname(dirname(__FILE__)))));
}

if (!defined("_MODPATH")) {
	define("_MODPATH", _ABSPATH.'/lib/Modules');
}

if (!defined("IDENT")) {
	define("IDENT", "CertIndex");
}

require _ABSPATH.'/lib/Autoload.php';

$view 	= new Zend_View;

try {
	$cache = new App_Cache('translate');
} catch (App_Exception $error) {
	echo $error->getMessage();
	exit;
}

Zend_Translate::setCache($cache->getCache());

$translate = App_Translate::getInstance();
Zend_Registry::set('Zend_Translate', $translate);

$front = Zend_Controller_Front::getInstance();
$front->throwExceptions(false);
$front->addModuleDirectory(_MODPATH);
$front->registerPlugin(new App_Controller_Plugin_Authentication());

$view->addHelperPath(_MODPATH.'/default/views/helpers/');
$viewRenderer = new Zend_Controller_Action_Helper_ViewRenderer($view);
Zend_Controller_Action_HelperBroker::addHelper($viewRenderer);

$front->dispatch();

?>
