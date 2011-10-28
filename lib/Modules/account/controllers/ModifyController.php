<?php

/**
* @author Tim Rupp
*/
class Account_ModifyController extends Zend_Controller_Action {
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
			'config' => $config,
			'module' => $this->_request->getModuleName(),
			'controller' => $this->_request->getControllerName(),
			'action' => $this->_request->getActionName(),
			'session' => $this->session
		));
	}

	public function editAction() {
		$auth = Zend_Auth::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$account = null;
		$username = null;

		$this->_request->setParamSources(array('_GET'));

		$accountId = $this->_request->getParam('id');
		$types = Authentication_Util::authTypes();

		if (empty($accountId)) {
			throw new Zend_Controller_Action_Exception('The supplied account ID was invalid');
		} elseif ($accountId == '_new') {
			// creating a new account;
		} else {
			$account = new Account($accountId);
		}

		$this->view->assign(array(
			'id' => $accountId,
			'account' => $account,
			'session' => $this->session,
			'types' => $types
		));
	}

	public function saveAction() {
		$status = false;
		$message = null;

		$log = App_Log::getInstance(self::IDENT);

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$id = $request->getParam('id');
		$username = $request->getParam('username');
		$password = $request->getParam('password');

		if ($password == '') {
			$handle = @fopen('/dev/urandom','rb');

			if ($handle) {
				$password = fread($handle,8);
				fclose($handle);
			}
		}

		try {
			if ($id == '_new') {
				$accountId = Account_Util::create($username);

				$account = new Account($accountId);
				$account->setPassword($password);

				$roleId = Role_Util::create($account->username, 'Default account role');
				$account->role->addRole($roleId);
				$account->setPrimaryRole($roleId);

				$status = true;
				$message = 'Successfully added the account';
			} else {
				throw new Exception('Only new accounts can be created via the Modify save method');
			}
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
