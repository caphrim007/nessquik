<?php

/**
* @author Tim Rupp
*/
class ErrorController extends Zend_Controller_Action {
	public $session;

	const IDENT = __CLASS__;

	public function init() {
		parent::init();

		$config = Ini_Config::getInstance();
		$auth = Zend_Auth::getInstance();
		$request = $this->getRequest();

		try {
			$sessionUser = $auth->getIdentity();
			$sessionId = Account_Util::getId($sessionUser);
			$this->session = new Account($sessionId);

			/**
			* This causes an infinite redirect after initial setup
			* if the policies directory is not writable. Remove this
			* check?
			*
			* if ($this->session->isFirstBoot()) {
			*	$this->_redirector = $this->_helper->getHelper('Redirector');
			*	$this->_redirector->gotoSimple('index', 'index', 'start');
			* }
			*/
		} catch (Exception $error) {
			$this->session = null;
		}

		$this->view->assign(array(
			'action' => $this->_request->getActionName(),
			'config' => $config,
			'controller' => $this->_request->getControllerName(),
			'module' => $this->_request->getModuleName(),
			'session' => $this->session
		));
	}

	public function errorAction() {
		try {
			$log = App_Log::getInstance(self::IDENT);
		} catch (App_Exception $error) {
			$this->view->message = $error->getMessage();
			return;
		}

		$errors = $this->_getParam('error_handler');
		$exception = $errors->exception;

		$log->err($exception->getMessage());
	}

	public function expiredCertAction() {
		$bc = new Browscap(_ABSPATH.'/var/cache/');
		$bc->localFile = _ABSPATH.'/etc/browscap.ini';
		$this->view->browser = $bc->getBrowser();
	}

	public function expiredXmlHttpAction() {
		$this->view->response = array(
			'status' => 'expired'
		);
	}

	public function permissionDeniedAction() {
		// Empty function to allow access to view
	}

	public function unwritableLogAction() {

	}
}

?>
