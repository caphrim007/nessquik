<?php

/**
* @author Tim Rupp
*/
class Account_LoginController extends Zend_Controller_Action {
	const IDENT = __CLASS__;

	public function init() {
		parent::init();

		$config = Ini_Config::getInstance();
		$auth = Zend_Auth::getInstance();

		$sessionUser = $auth->getIdentity();
		$sessionId = Account_Util::getId($sessionUser);

		if ($sessionId == 0) {
			$session = null;
		} else {
			$session = new Account($sessionId);
		}

		$this->view->assign(array(
			'action' => $this->_request->getActionName(),
			'config' => $config,
			'controller' => $this->_request->getControllerName(),
			'module' => $this->_request->getModuleName(),
			'session' => $session
		));
	}

	public function indexAction() {
		$certAuth = false;

		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);

		if (is_dir(_ABSPATH.'/opt/auth/cert') && Authentication_Util::hasAuthType('*Cert*')) {
			$certAuth = true;
		}

		$request = $this->getRequest();

		if ($request->isGet() && $config->misc->insecure_login && $this->_specifiedInsecureParams()) {
			$log->debug('Trying insecure login');

			$request->setParamSources(array('_GET'));

			$session = new Zend_Session_Namespace('nessquik');

			$this->standardLoginAction();
			$auth = Zend_Auth::getInstance();
			if ($auth->hasIdentity()) {
				$log->debug(sprintf('Insecure authentication successful for account "%s"', $auth->getIdentity()));

				$referer = $session->referer;

				if (!empty($referer)) {
					$session->referer = null;
					header(sprintf('Location: %s', $referer));
					exit;
				}
			}
		}

		$this->view->assign(array(
			'certAuth' => $certAuth
		));
	}

	/**
	* This method is only called by the Controller that is
	* instantiated inside of the opt/auth/cert/ directory.
	* It is useless to call it from the main controller
	* because there will be no cert data inside the
	* $_SERVER['SSL_CLIENT_CERT'] server variable.
	*/
	public function certificateLoginAction() {
		$status = false;
		$message = null;

		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$ini = Ini_Authentication::getInstance();
		$auth = Zend_Auth::getInstance();

		try {
			if (empty($_SERVER['SSL_CLIENT_CERT'])) {
				throw new Zend_Controller_Action_Exception('Client certificate was empty.');
			}

			$log->debug('Trying to parse client certificate');

			$adapter = new App_Auth_Adapter_Multiple($ini, null, null);

			$log->debug('Authenticating to adapter');
			$result = $auth->authenticate($adapter);

			$messages = array_filter($result->getMessages());
		
			foreach($messages as $message) {
				$log->debug($message);
			}

			if ($auth->hasIdentity()) {
				$log->debug('Certificate authentication successful');
				$sessionUser = $auth->getIdentity();
				$sessionId = Account_Util::getId($sessionUser);
				if ($sessionId == 0) {
					throw new Exception('You authenticated, but I cannot find your account. Was it created?');
				}

				$status = true;
			} else {
				$log->debug('Certificate authentication failed. Trying standard authentication');
				$status = false;
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

	public function standardLoginAction() {
		$status = false;
		$message = null;

		$config = Ini_Config::getInstance();
		$ini	= Ini_Authentication::getInstance();
		$auth	= Zend_Auth::getInstance();
		$log	= App_Log::getInstance(self::IDENT);

		try {
			$request = $this->getRequest();

			if ($request->isGet()) {
				$log->debug('Serving GET request');
				$request->setParamSources(array('_GET'));
			} else {
				$log->debug('Serving POST request');
				$request->setParamSources(array('_POST'));
			}

			$username = $request->getParam('username');

			$log->debug(sprintf('Trying to log in as user "%s"', $username));

			$password = $request->getParam('password');

			$adapter = new App_Auth_Adapter_Multiple($ini, $username, $password);

			try {
				$result = $auth->authenticate($adapter);
			} catch (Exception $error) {
				throw new Zend_Controller_Action_Exception($error->getMessage());
			}

			$messages = array_filter($result->getMessages());

			foreach($messages as $message) {
				$log->debug($message);
			}

			if ($auth->hasIdentity()) {
				$dataSession = Zend_Registry::get('nessquik');
				$dataSession->newLogin = true;
				Zend_Registry::set('nessquik', $dataSession);

				$status = true;
			} else {
				throw new Zend_Controller_Action_Exception('The username or password you entered was incorrect');
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

	public function logoutAction() {
		$auth = Zend_Auth::getInstance();
		$auth->clearIdentity();

		$this->_redirector = $this->_helper->getHelper('Redirector');
		$this->_redirector->gotoSimple('index', 'login', 'account');
	}

	protected function _specifiedInsecureParams() {
		$request = $this->getRequest();

		if ($request->isGet()) {
			$request->setParamSources(array('_GET'));

 	 		if ($request->getParam('username') !== null && $request->getParam('password') !== null) {
				return true;
			}
		}

		return false;
	}
}

?>
