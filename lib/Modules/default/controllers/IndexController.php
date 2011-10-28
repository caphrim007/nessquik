<?php

/**
* @author Tim Rupp
*/
class IndexController extends Zend_Controller_Action {
	public $session;

	const IDENT = __CLASS__;

	public function init() {
		parent::init();

		$config = Ini_Config::getInstance();
		$auth = Zend_Auth::getInstance();

		$sessionUser = $auth->getIdentity();
		$sessionId = Account_Util::getId($sessionUser);
		$this->session = new Account($sessionId);

		$this->view->assign(array(
			'action' => $this->_request->getActionName(),
			'config' => $config,
			'controller' => $this->_request->getControllerName(),
			'module' => $this->_request->getModuleName(),
			'session' => $this->session
		));
	}

	public function indexAction() {
		$module = 'dashboard';
		$log = App_Log::getInstance(self::IDENT);

		$account = $this->_helper->GetRequestedAccount();
		$redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector'); 
		$filter = new Zend_Filter_Boolean(Zend_Filter_Boolean::ALL);

		$dataSession = Zend_Registry::get('nessquik');
		$referer = $dataSession->referer;
		if (!empty($referer)) {
			$log->info(sprintf('Referer was not empty. Setting referer to empty and redirecting'));
			$options = array(
				'exit' => true,
				'prependBase' => false
			);
			$dataSession->referer = null;
			$dataSession->newLogin = false;

			Zend_Registry::set('nessquik', $dataSession);
			$log->info(sprintf('Redirecting user to URL "%s"', $referer));
			$this->_redirect($referer, $options);
		}

		$newLogin = $dataSession->newLogin;
		if ($filter->filter($newLogin)) {
			if (isset($account->settings->defaultModule)) {
				$module = $account->settings->defaultModule;
				$dataSession->newLogin = false;
				Zend_Registry::set('nessquik', $dataSession);
			}
		}

		$redirector->gotoSimple('index', 'index', $module);
	}
}

?>
