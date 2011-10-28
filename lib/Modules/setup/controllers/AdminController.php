<?php

/**
* @author Tim Rupp
*/
class Setup_AdminController extends Zend_Controller_Action {
	const IDENT = __CLASS__;

	public function init() {
		parent::init();

		$request = $this->getRequest();
		$config = Ini_Config::getInstance();

		if ($config->misc->firstboot == 0) {
			$redirector = $this->_helper->getHelper('Redirector');
			$redirector->gotoSimple('index', 'index', 'default');
		}

		$this->view->assign(array(
			'action' => $request->getActionName(),
			'config' => $config,
			'controller' => $request->getControllerName(),
			'module' => $request->getModuleName()
		));
	}

	public function indexAction() {
		$hasLdap = false;

		$auth = Ini_Authentication::getInstance();

		foreach($auth->auth as $id => $config) {
			if (strpos($config->adapter, 'Ldap') !== false) {
				$hasLdap = true;
				break;
			}
		}

		$this->view->assign(array(
			'hasLdap' => $hasLdap
		));
	}

	public function createAccountAction() {
		$status = false;
		$message = null;
		$addPermissions = array();

		$log = App_Log::getInstance(self::IDENT);
		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$id = $request->getParam('id');
		$username = $request->getParam('username');
		$password = $request->getParam('password');

		try {
			if (empty($password)) {
				throw new Zend_Controller_Action_Exception('The supplied password cannot be empty');
			}

			if (empty($username)) {
				throw new Zend_Controller_Action_Exception('The supplied username cannot be empty');
			}

			$log->debug(sprintf('Creating new account for "%s"', $username));
			$accountId = Account_Util::create($username);
			$permissions = new Permissions;

			$account = new Account($accountId);
			$account->setPassword($password);

			$log->debug('Creating primary role for new administrative user');
			$roleId = Role_Util::create($account->username, 'Default account role');

			$account->role->addRole($roleId);
			$account->setPrimaryRole($roleId);

			$role = new Role($roleId);

			$methods = $permissions->get('ApiMethod', null, 0, 0);
			$capabilities = $permissions->get('Capability', null, 0, 0);
			$queues = $permissions->get('Queue', null, 0, 0);
			$networkTargets = $permissions->get('NetworkTarget', null, 0, 0);
			$scanners = $permissions->get('Scanner', null, 0, 0);

			$addPermissions = array_merge($addPermissions, $methods);
			$addPermissions = array_merge($addPermissions, $capabilities);
			$addPermissions = array_merge($addPermissions, $queues);
			$addPermissions = array_merge($addPermissions, $networkTargets);
			$addPermissions = array_merge($addPermissions, $scanners);

			if (!empty($addPermissions)) {
				$log->debug('Adding new permissions to admin account');
				foreach($addPermissions as $permission) {
					$role->addPermission($permission['permission_id']);
				}
			}

			$status = true;
			$message = 'Successfully added the account';
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

	public function standardLoginAction() {
		$status = false;
		$message = null;
		$addPermissions = array();

		$config = Ini_Config::getInstance();
		$ini	= Ini_Authentication::getInstance();
		$auth	= Zend_Auth::getInstance();
		$log	= App_Log::getInstance(self::IDENT);

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));
		$username = $request->getParam('username');
		$password = $request->getParam('password');

		try {
			if (empty($password)) {
				throw new Zend_Controller_Action_Exception('The supplied password cannot be empty');
			}

			if (empty($username)) {
				throw new Zend_Controller_Action_Exception('The supplied username cannot be empty');
			}

			$log->debug('Setting up multiple auth adapter');
			$adapter = new App_Auth_Adapter_Multiple($ini, $username, $password);

			try {
				$log->debug('Trying to authenticate with multiple auth adapter');
				$result = $auth->authenticate($adapter);
			} catch (Exception $error) {
				throw new Zend_Controller_Action_Exception($error->getMessage());
			}

			$messages = array_filter($result->getMessages());

			foreach($messages as $message) {
				$log->debug($message);
			}

			if ($auth->hasIdentity()) {
				$log->debug('Successfully authenticated with the provided credentials');
				$status = true;

				$accountId = Account_Util::getId($username);
				$permissions = new Permissions;

				$account = new Account($accountId);
				$roleId = $account->primary_role;

				if (empty($roleId)) {
					$log->debug('Creating primary role for new administrative user');
					$roleId = Role_Util::create($account->username, 'Default account role');

					$account->role->addRole($roleId);
					$account->setPrimaryRole($roleId);
				} else {
					$log->debug('Primary role already exists');
				}

				$role = new Role($roleId);

				$methods = $permissions->get('ApiMethod', null, 0, 0);
				$capabilities = $permissions->get('Capability', null, 0, 0);
				$queues = $permissions->get('Queue', null, 0, 0);
				$networkTargets = $permissions->get('NetworkTarget', null, 0, 0);
				$scanners = $permissions->get('Scanner', null, 0, 0);

				$addPermissions = array_merge($addPermissions, $methods);
				$addPermissions = array_merge($addPermissions, $capabilities);
				$addPermissions = array_merge($addPermissions, $queues);
				$addPermissions = array_merge($addPermissions, $networkTargets);
				$addPermissions = array_merge($addPermissions, $scanners);

				if (!empty($addPermissions)) {
					$log->debug('Adding new permissions to admin account');
					foreach($addPermissions as $permission) {
						$role->addPermission($permission['permission_id']);
					}
				}
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
}

?>
