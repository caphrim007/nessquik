<?php

/**
* @author Tim Rupp
*/
class Admin_AuthenticationController extends Zend_Controller_Action {
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

		if (!$this->session->acl->isAllowed('Capability', array('admin_operator', 'edit_auth'))) {
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

	public function indexAction() {
		$log = App_Log::getInstance(self::IDENT);

		try {
			if (!is_writable(_ABSPATH.'/etc/local/authentication.conf')) {
				if (!is_writable(_ABSPATH.'/etc/local/')) {
					throw new Zend_Controller_Action_Exception('The location configuration directory is not writable');
				}
			} else if (file_exists(_ABSPATH.'/etc/local/authentication.conf') && !is_writable(_ABSPATH.'/etc/local/authentication.conf')) {
				throw new Zend_Controller_Action_Exception('The local authentication config file exists but is not writable');
			} else if (!is_writable(_ABSPATH.'/etc/local/authentication.conf')) {
				throw new Zend_Controller_Action_Exception('The local authentication config file is not writable');
			}

			$isWritable = true;
		} catch (Exception $error) {
			$isWritable = false;
			$message = $error->getMessage();
			$log->err($message);
		}

		$this->view->assign(array(
			'isWritable' => $isWritable,
		));
	}

	public function searchAction() {
		$auth = Ini_Authentication::getInstance();

		$this->view->assign(array(
			'auth' => $auth
		));
	}

	public function editAction() {
		$isNew = false;

		$auth = Ini_Authentication::getInstance();

		$request = $this->getRequest();
		$request->setParamSources(array('_GET'));

		$id = $request->getParam('id');

		if ($id == '_new') {
			$uuid = UUID::generate(UUID::UUID_RANDOM, UUID::FMT_STRING);
			$isNew = true;
		} else {
			$uuid = $id;
		}

		if (empty($uuid)) {
			throw new Zend_Controller_Action_Exception('The UUID provided to the controller was empty');
		} else {
			if (isset($auth->auth->$uuid)) {
				$info = $auth->auth->$uuid;
			}

			if (empty($info)) {
				$info = new Zend_Config(array());
			}
		}

		$this->view->assign(array(
			'id' => $uuid,
			'info' => $info,
			'isNew' => $isNew
		));
	}

	public function orderAction() {
		$status = false;
		$message = null;

		$result = array();

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$methods = $request->getParam('order');

		$log = App_Log::getInstance(self::IDENT);
		$auth = Ini_Authentication::getInstance();

		try {
			if (!is_writable(_ABSPATH.'/etc/local/authentication.conf')) {
				if (!is_writable(_ABSPATH.'/etc/local/')) {
					throw new Zend_Controller_Action_Exception('The location configuration directory is not writable');
				}
			} else if (file_exists(_ABSPATH.'/etc/local/authentication.conf') && !is_writable(_ABSPATH.'/etc/local/authentication.conf')) {
				throw new Zend_Controller_Action_Exception('The local authentication config file exists but is not writable');
			} else if (!is_writable(_ABSPATH.'/etc/local/authentication.conf')) {
				throw new Zend_Controller_Action_Exception('The local authentication config file is not writable');
			}

			foreach($methods as $key => $method) {
				$auth->auth->$method->priority = $key;
				$result['production']['auth'][$method] = $auth->auth->$method->toArray();
			}

			$config = new Zend_Config($result);
			$writer = new Zend_Config_Writer_Ini(array(
				'config'   => $config,
				'filename' => _ABSPATH.'/etc/local/authentication.conf'
			));
			$writer->write();
			$status = true;
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

	public function deleteAction() {
		$status = false;
		$message = null;

		$priority = 0;

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$id = $request->getParam('authenticationId');

		$log = App_Log::getInstance(self::IDENT);
		$auth = Ini_Authentication::getInstance()->toArray();

		try {
			if (!is_writable(_ABSPATH.'/etc/local/authentication.conf')) {
				if (!is_writable(_ABSPATH.'/etc/local/')) {
					throw new Zend_Controller_Action_Exception('The location configuration directory is not writable');
				}
			} else if (file_exists(_ABSPATH.'/etc/local/authentication.conf') && !is_writable(_ABSPATH.'/etc/local/authentication.conf')) {
				throw new Zend_Controller_Action_Exception('The local authentication config file exists but is not writable');
			} else if (!is_writable(_ABSPATH.'/etc/local/authentication.conf')) {
				throw new Zend_Controller_Action_Exception('The local authentication config file is not writable');
			}

			foreach($auth['auth'] as $key => $method) {
				if ($key == $id) {
					continue;
				} else {
					$auth['auth'][$key]['priority'] = $priority;
					$result['production']['auth'][$key] = $auth['auth'][$key];

					// For sanity's sake, reset the priorities
					$priority++;
				}
			}

			if (empty($result)) {
				$result = array();
			}

			$config = new Zend_Config($result);
			$writer = new Zend_Config_Writer_Ini(array(
				'config'   => $config,
				'filename' => _ABSPATH.'/etc/local/authentication.conf'
			));
			$writer->write();

			$status = true;
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

	public function saveAction() {
		$status = false;
		$message = null;

		$log = App_Log::getInstance(self::IDENT);
		$auth = Ini_Authentication::getInstance()->toArray();

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$params = $request->getParams();

		try {
			switch($params['auth-type']) {
				case 'Array':
					if (empty($params['username'])) {
						throw new Zend_Controller_Action_Exception('The username for the Array adapter cannot be empty');
					}

					$config = $this->_helper->CreateArrayAuth($params);
					break;
				case 'Cert':
					$config = $this->_helper->CreateCertAuth($params);
					break;
				case 'DbTable':
					$config = $this->_helper->CreateDbTableAuth($params);
					break;
				case 'Ldap':
					if (isset($params['bindRequiresDn']) && empty($params['username'])) {
						throw new Zend_Controller_Action_Exception('The username cannot be empty if binding requires a DN');
					}

					if (empty($params['baseDn'])) {
						throw new Zend_Controller_Action_Exception('The base Dn cannot be empty');
					}

					$config = $this->_helper->CreateLdapAuth($params);
					break;
				default:
					$config = null;
					break;
			}

			if (is_array($config)) {
				$oldAuth = new Zend_Config($auth, true);
				$newAuth = new Zend_Config($config);
				$oldAuth->merge($newAuth);

				$config = array(
					'production' => $oldAuth->toArray()
				);

				$config = new Zend_Config($config);

				$writer = new Zend_Config_Writer_Ini(array(
					'config'   => $config,
					'filename' => _ABSPATH.'/etc/local/authentication.conf'
				));
				$writer->write();
				$status = true;
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

	public function testLdapAction() {
		$status = false;
		$message = null;

		$log = App_Log::getInstance(self::IDENT);

		$params = array('test' => array());

		try {
			$adapter = new Zend_Auth_Adapter_Ldap($params, $username, $password);
			$status = $adapter->authenticate();
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
