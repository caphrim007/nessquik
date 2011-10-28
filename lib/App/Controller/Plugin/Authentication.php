<?php

/**
* @author Tim Rupp
*/
class App_Controller_Plugin_Authentication extends Zend_Controller_Plugin_Abstract {
	const IDENT = __CLASS__;

	public function routeShutdown(Zend_Controller_Request_Abstract $request) {
		$auth = Zend_Auth::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$controller = $request->getControllerName();
		$freePass = array('setup', 'about');

		if (in_array($request->getModuleName(), $freePass)) {
			return;
		} else if ($auth->hasIdentity()) {
			$config = Ini_Config::getInstance();

			$log->debug('User has a valid identity');

			$authSession = Zend_Registry::get('Zend_Auth');
			$dataSession = Zend_Registry::get('nessquik');

			$log->debug('Resetting expiration time for sessions');

			$authSession->setExpirationSeconds($config->misc->timeout);
			$dataSession->setExpirationSeconds($config->misc->timeout);

			$params = $request->getParams();
			$dataSession->redirect = array(
				'module' => $params['module'],
				'controller' => $params['controller'],
				'action' => $params['action']
			);

			Zend_Registry::set('nessquik', $dataSession);
			Zend_Registry::set('Zend_Auth', $authSession);

			return;
		} else if ($request->getControllerName() == 'login') {
			$log->debug('User requested the login controller');
			return;
		} else if (!$auth->hasIdentity() && $request->isXmlHttpRequest()) {
			$redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector'); 
			$redirector->gotoSimple('expired-xml-http', 'error', 'default');
			exit;
		} else {
			$dataSession = Zend_Registry::get('nessquik');

			if (isset($_SERVER['REQUEST_URI'])) {
				$dataSession->referer = $_SERVER['REQUEST_URI'];
				$log->info(sprintf('Session timed out or did not exist. Setting redirect URL to "%s"', $dataSession->referer));
			} else if ($dataSession->referer !== null) {
				# continue;
			} else {
				$dataSession->referer = null;
			}

			$dataSession->newLogin = false;

			Zend_Registry::set('nessquik', $dataSession);

			$params = $request->getParams();
			unset($params['module']);
			unset($params['controller']);
			unset($params['action']);
			$params = array_filter($params);

			$redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
			$redirector->gotoSimple('index', 'login', 'account', $request->getParams());
		}
	}
}

?>
