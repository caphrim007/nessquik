<?php

/**
* @author Tim Rupp
*/
class Scanners_ModifyController extends Zend_Controller_Action {
	public $session;

	const IDENT = __CLASS__;

	public function init() {
		parent::init();

		$config = Ini_Config::getInstance();
		$auth = Zend_Auth::getInstance();
		$request = $this->getRequest();

		$sessionUser = $auth->getIdentity();
		$sessionId = Account_Util::getId($sessionUser);
		$this->session = new Account($sessionId);

		if ($this->session->isFirstBoot()) {
			$this->_redirector = $this->_helper->getHelper('Redirector');
			$this->_redirector->gotoSimple('index', 'index', 'start');
		}

		if (!$this->session->acl->isAllowed('Capability', array('admin_operator', 'edit_scanner'))) {
			$this->_redirector = $this->_helper->getHelper('Redirector');
			$this->_redirector->gotoSimple('permission-denied', 'error', 'default');
		}

		$this->view->assign(array(
			'action' => $request->getActionName(),
			'config' => $config,
			'controller' => $request->getControllerName(),
			'module' => $request->getModuleName(),
			'session' => $this->session
		));
	}

	public function saveAction() {
		$status = false;
		$message = null;

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));
		$params = $request->getParams();

		$account = $this->_helper->GetRequestedAccount();
		$accountId = $account->id;

		$scannerId = $request->getParam('scannerId');
		if (empty($scannerId)) {
			throw new Zend_Controller_Action_Exception('The scanner ID provided to the controller was empty');
		}

		try {
			if ($scannerId == '_new') {
				$scannerId = Audit_Server_Util::create();
				if ($scannerId === false) {
					throw new Zend_Controller_Action_Exception('Could not create the new scanner');
				}

				$scanner = new Audit_Server($scannerId);
				$permission = new Permissions;

				$permission = $permission->get('Scanner', $scannerId);
				$result = $account->acl->allow($permission[0]['permission_id']);
			} else if ($this->_helper->CanChangeAudit($accountId, $scannerId)) {
				$scanner = new Audit_Server($scannerId);
			} else {
				$this->_redirector = $this->_helper->getHelper('Redirector');
				$this->_redirector->gotoSimple('permission-denied', 'error', 'default');
			}

			$scanner->name = $params['server-name'];
			$scanner->description = $params['description'];
			$scanner->adapter = $params['adapter'];
			$scanner->host = $params['host'];
			$scanner->port = $params['port'];
			$scanner->username = $params['username'];
			$scanner->password = $params['password'];

			$status = $scanner->update();
		} catch (Exception $error) {
			$log->err($error->getMessage());
			$status = false;
			$message = 'Failed to make the changes to the scanner';
		}

		$this->view->response = array(
			'status' => $status,
			'message' => $message
		);
	}

	public function editAction() {
		$isNew = false;
		$scanner = null;

		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$today = new Zend_Date;

		$request = $this->getRequest();
		$request->setParamSources(array('_GET'));

		$scannerId = $request->getParam('id');
		if (empty($scannerId)) {
			$scannerId = '_new';
		}

		$account = $this->_helper->GetRequestedAccount();
		$accountId = $account->id;

		if ($scannerId == '_new') {
			$isNew = true;
		} else {
			$scanner = new Audit_Server($scannerId);
		}

		$this->view->assign(array(
			'scanner' => $scanner,
			'id' => $scannerId,
			'isNew' => $isNew,
		));
	}
}

?>
