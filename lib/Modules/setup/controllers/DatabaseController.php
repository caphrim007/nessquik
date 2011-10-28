<?php

/**
* @author Tim Rupp
*/
class Setup_DatabaseController extends Zend_Controller_Action {
	protected $_configFile;

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

	}

	public function testAction() {
		$status = false;
		$message = null;
		$tmp = array();

		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$auth = Ini_Authentication::getInstance()->toArray();

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$params = $request->getParams();

		try {
			$instance = $config->instance;
			unset($config->instance);

			$config->database->default->params->username = $params['username'];
			$config->database->default->params->password = $params['password'];
			$config->database->default->params->host = $params['host'];
			$config->database->default->params->port = $params['port'];
			$config->database->default->params->dbname = $params['dbname'];

			$db = App_Db::getInstance($config->database->default);

			$sql = $db->select()->from('audits');

			$stmt = $sql->query();
			$stmt->rowCount();

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

		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$auth = Ini_Authentication::getInstance()->toArray();

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$params = $request->getParams();

		try {
			if (!is_writeable(dirname($this->_configFile)) && (file_exists($this->_configFile) && !is_writable($this->_configFile))) {
				throw new Zend_Controller_Action_Exception('The config file, or local config directory, is not writable');
			}

			$tmp = array();
			$instance = $config->instance;
			unset($config->instance);

			$config->database->default->params->username = $params['username'];
			$config->database->default->params->password = $params['password'];
			$config->database->default->params->host = $params['host'];
			$config->database->default->params->port = $params['port'];
			$config->database->default->params->dbname = $params['dbname'];

			$tmp[$instance] = $config;
			$tmp['config']['instance'] = $instance;

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
