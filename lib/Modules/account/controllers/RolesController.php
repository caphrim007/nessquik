<?php

/**
* @author Tim Rupp
*/
class Account_RolesController extends Zend_Controller_Action {
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
		$this->_request->setParamSources(array('_GET'));

		$id = $this->_request->getParam('accountId');
		if (!is_numeric($id)) {
			throw new Exception('The specified ID is invalid');
		}

		$account = new Account($id);
		$selectedRoles = $account->getRoles();
		$allRoles = Role_Util::getRoles(1,null);

		$this->view->assign(array(
			'account' => $account,
			'allRoles' => $allRoles,
			'accountId' => $id,
			'selectedRoles' => $selectedRoles
		));
	}

	public function saveAction() {
		$status = false;
		$message = null;

		$log = App_Log::getInstance(self::IDENT);
		$this->_request->setParamSources(array('_POST'));

		$accountId = $this->_request->getParam('accountId');
		$selectedRoles = $this->_request->getParam('selected-role');
		$primaryRole = $this->_request->getParam('primary-role');

		if (!is_numeric($accountId)) {
			throw new Exception('The specified ID is invalid');
		}

		$account = new Account($accountId);
		$roles = $account->getRoles();

		try {
			if (empty($selectedRoles)) {
				throw new Account_Exception('You must select at least one role for the account');
			} else if (is_array($selectedRoles)) {
				$account->role->clear();
				foreach($selectedRoles as $key => $roleId) {
					$result = $account->role->addRole($roleId);
				}
			}

			$result = $account->setPrimaryRole($primaryRole);
			$status = true;
		} catch (Account_Exception $error) {
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
