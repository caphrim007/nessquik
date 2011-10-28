<?php

/**
* @author Tim Rupp
*/
class Settings_ContactController extends Zend_Controller_Action {
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

		$this->view->assign(array(
			'account' => $this->session,
		));
	}

	public function saveAction() {
		$status = false;
		$message = null;

		$log = App_Log::getInstance(self::IDENT);

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$properName = $request->getParam('proper_name');
		$emailContact = $request->getParam('emailContact');
		$xmppContact = $request->getParam('xmppContact');

		try {
			$this->session->proper_name = $properName;

			$this->session->contacts->email = array();
			$this->session->contacts->email = $this->_helper->FilterBogusAddress($emailContact);
			$this->session->contacts->xmpp = array();
			$this->session->contacts->xmpp = $this->_helper->FilterBogusAddress($xmppContact);

			$status = $this->session->contacts->update();
		} catch (Account_Exception $error) {
			$status = false;
			$message = 'Failed to update your contacts';
			$log->err($error->getMessage());
		}

		$this->view->response = array(
			'status' => $status,
			'message' => $message
		);
	}
}

?>
