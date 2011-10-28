<?php

/**
* @author Tim Rupp
*/
class Account_PasswordController extends Zend_Controller_Action {
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

		if (!$this->session->acl->isAllowed('Capability', 'edit_user')) {
			$this->_redirector = $this->_helper->getHelper('Redirector');
			$this->_redirector->gotoSimple('permission-denied', 'error', 'default');
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
		$auth = Zend_Auth::getInstance();
		$log = App_Log::getInstance(self::IDENT);

		$this->_request->setParamSources(array('_GET'));

		$accountId = $this->_request->getParam('accountId');
		if (!is_numeric($accountId)) {
			throw new Exception('The specified ID is invalid');
		}

		$account = new Account($accountId);
		$types = Authentication_Util::authTypes();

		$this->view->assign(array(
			'accountId' => $accountId,
			'account' => $account,
			'types' => $types
		));
	}

	public function saveAction() {
		$status = false;
		$message = null;

		$log = App_Log::getInstance(self::IDENT);

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$accountId = $request->getParam('accountId');
		$newPassword = $request->getParam('newPassword');
		$repeatPassword = $request->getParam('repeatPassword');

		try {
			if (!is_numeric($accountId)) {
				throw new Exception('The specified ID is invalid');
			} else if ($newPassword != $repeatPassword) {
				throw new Exception('The passwords you typed did not match');
			}

			$account = new Account($accountId);
			$result = $account->setPassword($newPassword);

			if ($result === true) {
				$status = true;
				$log->info('Successfully changed the password');
			} else {
				throw new Exception('Failed to change the password');
			}
		} catch (Exception $error) {
			$status = false;
			$message = $error->getMessage();
			$log->err($message);
		}

		$this->view->response = array(
			'status' => $status,
			'message' => $message
		);
	}
}

?>
