<?php

/**
* @author Tim Rupp
*/
class Setup_ScannerController extends Zend_Controller_Action {
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
		$servers = Audit_Server_Util::getServers(1,10);

		$this->view->assign(array(
			'servers' => $servers
		));
	}

	public function saveAction() {
		$status = false;
		$message = null;

		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));
		$params = $request->getParams();

		$id = UUID::generate(UUID::UUID_RANDOM, UUID::FMT_STRING);

		try {
			$scannerId = Audit_Server_Util::create();

			$scanner = new Audit_Server($scannerId);

			$scanner->name = $params['server-name'];
			$scanner->description = $params['description'];
			$scanner->adapter = $params['adapter'];
			$scanner->host = $params['host'];
			$scanner->port = $params['port'];
			$scanner->username = $params['username'];
			$scanner->password = $params['password'];
			$scanner->pluginDir = $params['pluginDir'];
			$scanner->for_update = true;

			$status = $scanner->update();

			if (!is_writeable(dirname($this->_configFile)) && (file_exists($this->_configFile) && !is_writable($this->_configFile))) {
				throw new Zend_Controller_Action_Exception('The config file, or local config directory, is not writable');
			}

			$tmp = array();
			$instance = $config->instance;
			unset($config->instance);

			$config->vscan->default = $scannerId;

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

	public function updatePluginsAction() {
		set_time_limit(0);

		$status = false;
		$message = null;

		$log = App_Log::getInstance(self::IDENT);

		try {
			$controller = Maintenance_Engine::getInstance();
			$plugin = new Maintenance_Plugin_CreatePlugins;
			$controller->registerPlugin($plugin);
			$controller->considerCron(false);
			$controller->dispatch();

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

		try {
			$servers = Audit_Server_Util::getServers(1,10);
			foreach($servers as $server) {
				$scanner = new Audit_Server($server['id']);
				$scanner->delete();
			}

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
