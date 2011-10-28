<?php

/**
* @author Tim Rupp
*/
class App_Controller_Plugin_FirstBoot extends Zend_Controller_Plugin_Abstract {
	const IDENT = __CLASS__;

	public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$freePass = array('setup', 'about');

		if (in_array($request->getModuleName(), $freePass)) {
			return;
		} else {
			if ($config->misc->firstboot == 1) {
				$redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector'); 
				$redirector->gotoSimple('index', 'index', 'setup');
			} else {
				return;
			}
		}
	}
}

?>
