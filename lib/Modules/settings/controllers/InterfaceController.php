<?php

/**
* @author Tim Rupp
*/
class Settings_InterfaceController extends Zend_Controller_Action {
	public $session;

	const IDENT = __CLASS__;

	public function init() {
		parent::init();

		$config = Ini_Config::getInstance();
		$auth = Zend_Auth::getInstance();

		$sessionUser = $auth->getIdentity();
		$sessionId = Account_Util::getId($sessionUser);
		$this->session = new Account($sessionId);

		if ($this->session->isFirstBoot()) {
			$this->_redirector = $this->_helper->getHelper('Redirector');
			$this->_redirector->gotoSimple('index', 'index', 'start');
		}

		$this->view->assign(array(
			'action' => $this->_request->getActionName(),
			'config' => $config,
			'controller' => $this->_request->getControllerName(),
			'module' => $this->_request->getModuleName(),
			'session' => $this->session
		));
	}

	public function indexAction() {
		$request = $this->getRequest();
		$request->setParamSources(array('_GET'));

		$this->view->assign(array(
			'account' => $this->session
		));
	}

	public function saveAction() {
		$status = false;
		$message = null;

		$log = App_Log::getInstance(self::IDENT);

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$limits = $request->getParams();

		try {
			$this->session->settings->limitAccounts = $limits['limitAccounts'];
			$this->session->settings->limitAudits = $limits['limitAudits'];
			$this->session->settings->limitMappings = $limits['limitMappings'];
			$this->session->settings->limitPolicies = $limits['limitPolicies'];
			$this->session->settings->limitRoles = $limits['limitRoles'];
			$this->session->settings->limitScanners = $limits['limitScanners'];
			$this->session->settings->defaultPolicyView = $limits['defaultPolicyView'];

			$status = $this->session->settings->update();
		} catch (Exception $error) {
			$log->err($error->getMessage());
			$status = false;
			$message = $error->getMessage();
		}

		$this->view->response = array(
			'status' => $status,
			'message' => $message
		);
	}
}

?>
