<?php

/**
* @author Tim Rupp
*/
class Setup_AccountsController extends Zend_Controller_Action {
	const IDENT = __CLASS__;

	public function init() {
		parent::init();

		$this->_configFile = _ABSPATH.'/etc/local/config.conf';

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
		$count = 0;
		$dir = new DirectoryIterator(_ABSPATH.'/opt/setup/accounts/');
		foreach($dir as $accounts ) {
			$count++;
		}

		if ($count == 0) {
			$redirector = $this->_helper->getHelper('Redirector');
			$redirector->gotoUrl('/setup/finalize');
			exit;
		}
	}

	public function createAction() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);
		$permissions = new Permissions;
		$tmpAccts = array();
		$results = array();
		$addPermissions = array();
		$options = array('all');
		$result = array();

		try {
			$dir = new DirectoryIterator(_ABSPATH.'/opt/setup/accounts/');
			foreach($dir as $accounts ) {
				if(!$accounts->isDot()) {
					$tmpAccts = array_merge($tmpAccts, file($accounts->getPathname()));
				}
			}

			$totalPasswords = count($tmpAccts);
			$passwords = $this->_helper->randomPassword(12,$totalPasswords,$options);

			foreach($tmpAccts as $key => $acctName) {
				if (Account_Util::exists($acctName)) {
					$log->info('The specified account name already exists in the database');
				} else {
					$acctId = Account_Util::create($acctName, true);
					$roleId = Role_Util::create($acctName, 'Default account role');

					$account = new Account($acctId);
					$role = new Role($roleId);

					$account->setPassword($passwords[$key]);
					$account->role->addRole($roleId);
					$account->setPrimaryRole($roleId);

					$addPermissions = $permissions->get('ApiMethod', null, 0, 0);

					if (!empty($addPermissions)) {
						$log->debug('Adding new permissions to admin account');
						foreach($addPermissions as $permission) {
							$role->addPermission($permission['permission_id']);
						}
					}

					$results[] = $account;

					if ($account->username == '_api') {
						$log->debug('Special account "_api" created. Adding credentials to local configuration file');
						if (!is_writeable(dirname($this->_configFile)) && 
							(file_exists($this->_configFile) && !is_writable($this->_configFile))) {
							throw new Zend_Controller_Action_Exception('The config file, or local config directory, is not writable');
						}

						$tmp = array();
						$configArray = $config->toArray();
						$instance = $config->instance;
						unset($config->instance);

						$configArray['ws']['api']['nq']['username'] = $account->username;
						$configArray['ws']['api']['nq']['password'] = $account->password;

						$tmp[$instance] = $configArray;
						$tmp['config']['instance'] = $instance;

						$newConfig = new Zend_Config($tmp);

						$writer = new Zend_Config_Writer_Ini(array(
							'config'   => $newConfig,
							'filename' => $this->_configFile
						));

						$writer->write();
						$log->info('The configuration options specifying an API username and password have been saved');
					}
				}
			}
		} catch (Exception $error) {
			$log->err($error->getMessage());
		}

		$this->view->accounts = $results;
	}
}

?>
