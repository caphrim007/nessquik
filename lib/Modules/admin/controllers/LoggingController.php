<?php

/**
* @author Tim Rupp
*/
class Admin_LoggingController extends Zend_Controller_Action {
	public $session;

	protected $_logLevels;
	protected $_configFile;

	const IDENT = __CLASS__;

	public function init() {
		parent::init();

		$this->_logLevels = array('error', 'warning', 'info', 'debug');
		$this->_configFile = _ABSPATH.'/etc/local/config.conf';

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

		if (!$this->session->acl->isAllowed('Capability', array('admin_operator', 'edit_logging'))) {
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
		$this->view->assign(array(
			'logLevels' => $this->_logLevels
		));
	}

	public function writableAction() {
		$status = false;
		$message = null;

		$log = App_Log::getInstance(self::IDENT);

		try {
			if (!is_writeable(dirname($this->_configFile))) {
				throw new Zend_Controller_Action_Exception('The local config directory is not writable');
			} else if (file_exists($this->_configFile) && !is_writable($this->_configFile)) {
				throw new Zend_Controller_Action_Exception('The local config file exists but is not writable');
			} else {
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

	public function saveAction() {
		$status = false;
		$message = null;

		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$logLevel = $request->getParam('log-level');
		$logs['messages'] = $request->getParam('log-messages');
		$logs['xmlrpc'] = $request->getParam('log-xmlrpc');
		$logs['cron'] = $request->getParam('log-cron');

		try {
			if (in_array($logLevel, $this->_logLevels)) {
				$config->debug->log->mask = $logLevel;
			} else {
				throw new Zend_Controller_Action_Exception('The specified log level was not found');
			}

			foreach($logs as $key => $val) {
				if (!is_writeable(dirname($val)) || (file_exists($val) && !is_writable($val))) {
					$message = sprintf('%s log file is not writable! Check the file and directory permissions.', $val);
					throw new Zend_Controller_Action_Exception($message);
				}
			}

			$tmp = array();
			$instance = $config->instance;
			unset($config->instance);

			$tmp[$instance] = $config->toArray();
			$tmp['config']['instance'] = $instance;

			$tmp[$instance]['debug']['log']['mask'] = $logLevel;
			$tmp[$instance]['debug']['log']['messages'] = $logs['messages'];
			$tmp[$instance]['debug']['log']['xmlrpc'] = $logs['xmlrpc'];
			$tmp[$instance]['debug']['log']['cron'] = $logs['cron'];

			$newConfig = new Zend_Config($tmp);
			$writer = new Zend_Config_Writer_Ini(array(
				'config'   => $newConfig,
				'filename' => $this->_configFile
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
}

?>
