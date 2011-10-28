<?php

/**
* @author Tim Rupp
*/
class App_Controller_Plugin_CanLogin extends Zend_Controller_Plugin_Abstract {
	const IDENT = __CLASS__;

	public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request) {
		$config = Ini_Config::getInstance();

		$module = $request->getModuleName();
		$controller = $request->getControllerName();
		$action = $request->getActionName();
		$freePass = array('setup', 'about');

		if (in_array($request->getModuleName(), $freePass)) {
			return;
		} else if ($module == 'account' && $controller == 'login') {
			return;
		} else {
			$auth = Zend_Auth::getInstance();

			$sessionUser = $auth->getIdentity();
			$sessionId = Account_Util::getId($sessionUser);
			if ($sessionId == '0') {
				$redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector'); 
				$redirector->gotoSimpleAndExit('index', 'login', 'account');
			} else {
				return;
			}
		}
	}
}

?>
