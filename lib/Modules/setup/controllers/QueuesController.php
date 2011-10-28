<?php

/**
* @author Tim Rupp
*/
class Setup_QueuesController extends Zend_Controller_Action {
	protected $_configFile;

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
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);
		$permissions = new Permissions;

		$sql = $db->select()->from('queue');
		$stmt = $sql->query();
		$results = $stmt->fetchAll();

		foreach($results as $queue) {
			$result = $this->createQueue($queue['queue_name']);

			if (!$permissions->exists('Queue', $queue['queue_id'])) {
				$result = $permissions->add('Queue', $queue['queue_id']);
			}
		}

		$this->view->queues = $results;
	}

	protected function createQueue($name) {
		$configPath = sprintf('%s/etc/local/config.conf', _ABSPATH);

		$config = Ini_Config::get();
		$log = App_Log::getInstance(self::IDENT);

		try {
			if (file_exists($configPath) && !is_writable($configPath)) {
				throw new Exception('The config file exists, but is not writable by nessquik');
			} else if (!file_exists($configPath) && !is_writable(dirname($configPath))) {
				throw new Exception('The local config file does not exist and the local config directory is not writable, so nessquik cannot create it');
			}

			$options = array(
				'name' => $name,
				'driverOptions' => array(
					'host' => $config->database->default->params->host,
					'port' => $config->database->default->params->port,
					'username' => $config->database->default->params->username,
					'password' => $config->database->default->params->password,
					'dbname' => $config->database->default->params->dbname,
					'type' => $config->database->default->adapter
				),
				'options' => array(
					'forupdate' => true
				)
			);

			$queue = new Zend_Queue('Db', $options);
			if (Queue_Util::exists($name)) {
				$log->info('A queue with this name already exists. Skipping ping');
			} else {
				$queue->send('ping');
				$count = $queue->count();
				if($count == 1) {
					$messages = $queue->receive(1);
					foreach($messages as $message) {
						$queue->deleteMessage($message);
					}
				} else {
					$log->debug('Failed to send a message to the queue');
				}
			}

			$tmp = array();
			$instance = $config->instance;
			unset($config->instance);

			$tmp[$instance] = $config->toArray();
			$tmp['config']['instance'] = $instance;
			$tmp[$instance]['queue'][$name] = $options;

			$newConfig = new Zend_Config($tmp);
			$writer = new Zend_Config_Writer_Ini(array(
				'config'   => $newConfig,
				'filename' => $configPath
			));
			$writer->write();

			return true;
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}
}

?>
