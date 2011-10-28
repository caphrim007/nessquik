<?php

/**
* @author Tim Rupp
*/
class Admin_MaintenanceController extends Zend_Controller_Action {
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

		if (!$this->session->acl->isAllowed('Capability', array('admin_operator', 'edit_maintenance'))) {
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
		$results = array();
		$config = Ini_Maintenance::getInstance();

		if (empty($config)) {
			$info = new Zend_Config(array());
		} else {
			$info = $config;
		}

		$this->view->assign(array(
			'info' => $info->toArray()
		));
	}

	public function saveAction() {
		$status = true;
		$message = null;

		$maintenance = Ini_Maintenance::getInstance()->toArray();
		$log = App_Log::getInstance(self::IDENT);

		$instance = $maintenance['instance'];
		$config = array(
			$instance => array(
				'plugins' => array()
			),
			'config' => array(
				'instance' => null
			)
		);

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));
		$params = $request->getParams();

		$config[$instance]['plugins']['directory']['system'] = $params['system-directory'];
		$config[$instance]['plugins']['directory']['user'] = $params['user-directory'];
		$config[$instance]['plugins']['single']['path'] = $params['single-path'];

		foreach($params['include'] as $plugin) {
			if(empty($plugin)) {
				continue;
			} else {
				$plugin = preg_replace('/[^a-z0-9_-]/i','',$plugin);
				$config[$instance]['plugins']['single']['register'][] = $plugin;
			}
		}

		foreach($params['exclude'] as $plugin) {
			if(empty($plugin)) {
				continue;
			} else {
				$plugin = preg_replace('/[^a-z0-9_-]/i','',$plugin);
				$config[$instance]['plugins']['single']['unregister'][] = $plugin;
			}
		}

		$config['config']['instance'] = $maintenance['instance'];
		$config = new Zend_Config($config);

		$writer = new Zend_Config_Writer_Ini(array(
			'config'   => $config,
			'filename' => sprintf('%s/etc/local/maintenance.conf', _ABSPATH)
		));

		try {
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
}

?>
