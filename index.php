<?php

if (!defined("_ABSPATH")) {
	define("_ABSPATH", dirname(__FILE__));
}

if (!defined("_MODPATH")) {
	define("_MODPATH", _ABSPATH.'/lib/Modules');
}

if (!defined("IDENT")) {
	define("IDENT", "Index");
}

require _ABSPATH.'/lib/Autoload.php';

$view = new Zend_View;
Zend_Session::start();

$authSession = new Zend_Session_Namespace('Zend_Auth');
$dataSession = new Zend_Session_Namespace('nessquik');

Zend_Registry::set('Zend_Auth', $authSession);
Zend_Registry::set('nessquik', $dataSession);

$config = Ini_Config::getInstance();

$front = Zend_Controller_Front::getInstance();
$front->throwExceptions(false);
$front->addModuleDirectory(_MODPATH);

$front->registerPlugin(new App_Controller_Plugin_CheckUnwritable());
$front->registerPlugin(new App_Controller_Plugin_InitCache());
$front->registerPlugin(new App_Controller_Plugin_Authentication());
$front->registerPlugin(new App_Controller_Plugin_FirstBoot());
$front->registerPlugin(new App_Controller_Plugin_CanLogin());

if ($config->debug->log->mask == 'debug') {
	$front->registerPlugin(new App_Controller_Plugin_Profiling());
}

$view->addHelperPath(_MODPATH.'/default/views/helpers/', 'App_View_Helper');
$viewRenderer = new Zend_Controller_Action_Helper_ViewRenderer($view);
Zend_Controller_Action_HelperBroker::addHelper($viewRenderer);
Zend_Controller_Action_HelperBroker::addPath(_ABSPATH.'/lib/App/Controller/Helper', 'App_Controller_Helper');

$front->dispatch();

?>
