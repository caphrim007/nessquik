<?php

/**
* @author Tim Rupp
*/
class Setup_FinalizeController extends Zend_Controller_Action {
	protected $_configFile;

	const IDENT = __CLASS__;

	public function init() {
		parent::init();

		$this->_configFile = _ABSPATH.'/etc/local/config.conf';

		$config = Ini_Config::getInstance();

		if ($config->misc->firstboot == 0) {
			$redirector = $this->_helper->getHelper('Redirector');
			$redirector->gotoSimple('index', 'index', 'default');
		}

		$this->view->assign(array(
			'action' => $this->_request->getActionName(),
			'config' => $config,
			'controller' => $this->_request->getControllerName(),
			'module' => $this->_request->getModuleName()
		));
	}

	public function indexAction() {
		$log = App_Log::getInstance(self::IDENT);
		$config = Ini_Config::getInstance();

		try {
			if (!is_writable($this->_configFile)) {
				if (!is_writable(_ABSPATH.'/etc/local/')) {
					throw new Zend_Controller_Action_Exception('The location configuration directory is not writable');
				}
			} else if (file_exists($this->_configFile) && !is_writable($this->_configFile)) {
				throw new Zend_Controller_Action_Exception('The local authentication config file exists but is not writable');
			} else if (!is_writable($this->_configFile)) {
				throw new Zend_Controller_Action_Exception('The local authentication config file is not writable');
			}

			$tmp = array();
			$instance = $config->instance;
			unset($config->instance);

			$config->misc->firstboot = 0;

			$tmp[$instance] = $config;
			$tmp['config']['instance'] = $instance;

			$newConfig = new Zend_Config($tmp);

			$writer = new Zend_Config_Writer_Ini(array(
				'config'   => $newConfig,
				'filename' => $this->_configFile
			));

			$writer->write();
			$log->info('The configuration options have been saved');

			$log->debug('Creating default account role and assigning initial scanner to it');

			$roleId = $this->_createDefaultRole();
			$this->_assignRoleToAccounts($roleId);
			$this->_createAccountDefaultsIni($roleId);

			$status = true;
		} catch (Exception $error) {
			$status = false;
			$message = $error->getMessage();
			$log->err($message);
		}
	}

	protected function _createDefaultRole() {
		$id = Role_Util::create('_default', 'Default account role assigned to all users');

		$permissions = new Permissions;
		$role = new Role($id);
		$role->clearPermissions();

		$servers = Audit_Server_Util::getServers(1,0);
		foreach($servers as $server) {
			$permission = $permissions->get('Scanner', $server['id']);
			$role->addPermission($permission[0]['permission_id']);
		}

		return $id;
	}

	protected function _assignRoleToAccounts($defaultRoleId) {
		$accounts = Account_Util::getAccounts(1,0);

		foreach($accounts as $acct) {
			$account = new Account($acct['id']);
			$account->role->addRole($defaultRoleId);
		}
	}

	protected function _createAccountDefaultsIni($defaultRoleId) {
		$configFile = _ABSPATH.'/etc/local/account-defaults.conf';
		$config = Ini_AccountDefaults::getInstance();
		$log = App_Log::getInstance(self::IDENT);

		$selectedRoles = array($defaultRoleId);

		try {
			if (!is_writeable(dirname($configFile)) && (file_exists($configFile) && !is_writable($configFile))) {
				throw new Zend_Controller_Action_Exception('The config file, or local config directory, is not writable');
			}

			$tmp = array();
			$instance = $config->instance;
			unset($config->instance);

			$tmp[$instance] = array();
			$tmp['config']['instance'] = $instance;

			if (empty($selectedRoles)) {
				$tmp[$instance]['roles'] = array();
			} else {
				foreach($selectedRoles as $key => $val) {
					$role = new Role($val);
					$tmp[$instance]['roles'][$val] = $role->name;
				}
			}

			$newConfig = new Zend_Config($tmp);

			$writer = new Zend_Config_Writer_Ini(array(
				'config'   => $newConfig,
				'filename' => $configFile
			));

			$writer->write();
			$log->info('The configuration options have been saved');
			return true;
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}
}

?>
