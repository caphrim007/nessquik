<?php

/**
* @author Tim Rupp
*/
class Admin_AccountDefaultsController extends Zend_Controller_Action {
	public $session;

	protected $_configFile;

	const IDENT = __CLASS__;

	public function init() {
		parent::init();

		$this->_configFile = _ABSPATH.'/etc/local/account-defaults.conf';

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

		if (!$this->session->acl->isAllowed('Capability', array('admin_operator', 'edit_role'))) {
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
		$allRoles = array();
		$selectedRoles = array();

		$log = App_Log::getInstance(self::IDENT);
		$config = Ini_AccountDefaults::getInstance();

		if (!is_writeable(dirname($this->_configFile)) && (file_exists($this->_configFile) && !is_writable($this->_configFile))) {
			$message = 'The config file, or local config directory, is not writable';
			$log->err($message);
			$this->view->error = $message;
		}

		$allRoles = Role_Util::getRoles(1,null);

		if (isset($config->roles)) {
			$selectedRoles = $config->roles->toArray();
		}

		$this->view->assign(array(
			'allRoles' => $allRoles,
			'selectedRoles' => $selectedRoles
		));
	}

	public function saveAction() {
		$status = false;
		$message = null;

		$config = Ini_AccountDefaults::getInstance();
		$log = App_Log::getInstance(self::IDENT);

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$selectedRoles = $request->getParam('selected-role');

		try {
			if (!is_writeable(dirname($this->_configFile)) && (file_exists($this->_configFile) && !is_writable($this->_configFile))) {
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
				'filename' => $this->_configFile
			));

			$writer->write();
			$log->info('The configuration options have been saved');
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
}

?>
