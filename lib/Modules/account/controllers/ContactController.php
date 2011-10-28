<?php

/**
* @author Tim Rupp
*/
class Account_ContactController extends Zend_Controller_Action {
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

		$this->view->assign(array(
			'action' => $request->getActionName(),
			'config' => $config,
			'controller' => $request->getControllerName(),
			'module' => $request->getModuleName(),
			'session' => $this->session
		));
	}

	public function indexAction() {
		$request = $this->getRequest();
		$request->setParamSources(array('_GET'));

		$id = $request->getParam('accountId');
		if (!is_numeric($id)) {
			throw new Exception('The specified Account ID is invalid');
		}

		$account = new Account($id);

		$this->view->assign(array(
			'account' => $account,
			'accountId' => $id,
			'session' => $this->session
		));
	}

	public function saveAction() {
		$status = false;
		$message = null;

		$log = App_Log::getInstance(self::IDENT);

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$accountId = $request->getParam('accountId');
		$properName = $request->getParam('proper_name');

		if (!is_numeric($accountId)) {
			throw new Exception('The specified Account ID is invalid');
		}

		$account = new Account($accountId);
		$emailContact = $request->getParam('emailContact');
		$xmppContact = $request->getParam('xmppContact');

		try {
			$account->proper_name = $properName;

			$account->doc->emailContact = array();
			$account->doc->emailContact = $this->_helper->FilterBogusAddress($emailContact);
			$account->doc->xmppContact = array();
			$account->doc->xmppContact = $this->_helper->FilterBogusAddress($xmppContact);
			$account->doc->phoneContact = array();
			$account->update();

			$status = true;
		} catch (Account_Exception $error) {
			$status = false;
			$message = 'Failed to update the account contacts';
			$log->err($error->getMessage());
		}

		$this->view->response = array(
			'status' => $status,
			'message' => $message
		);
	}
}

?>
