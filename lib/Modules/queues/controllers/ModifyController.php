<?php

/**
* @author Tim Rupp
*/
class Queues_ModifyController extends Zend_Controller_Action {
	public $session;

	const IDENT = __CLASS__;

	public function init() {
		parent::init();

		$config = Ini_Config::getInstance();
		$auth = Zend_Auth::getInstance();

		$sessionUser = $auth->getIdentity();
		$sessionId = Account_Util::getId($sessionUser);
		$this->session = new Account($sessionId);
		$request = $this->getRequest();

		if ($this->session->isFirstBoot()) {
			$this->_redirector = $this->_helper->getHelper('Redirector');
			$this->_redirector->gotoSimple('index', 'index', 'start');
		}

		if (!$this->session->acl->isAllowed('Capability', array('admin_operator', 'edit_queue'))) {
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

	public function saveAction() {
		$status = false;
		$message = null;

		$configPath = sprintf('%s/etc/local/config.conf', _ABSPATH);

		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$params = $request->getParams();
		
		try {
			if (file_exists($configPath) && !is_writable($configPath)) {
				throw new Exception('The config file exists, but is not writable');
			} else if (!file_exists($configPath) && !is_writable(dirname($configPath))) {
				throw new Exception('The local config file does not exist and the local config directory is not writable, so it cannot be created');
			}

			if (($params['id'] == '_new') && Queue_Util::exists($params['queue-name'])) {
				throw new Exception('A queue with this name already exists!');
			}

			$options = array(
				'name' => $params['queue-name'],
				'driverOptions' => array(
					'host' => $params['host'],
					'port' => $params['port'],
					'username' => $params['username'],
					'password' => $params['password'],
					'dbname' => $params['dbname'],
					'type' => $params['type']
				),
				'options' => array(
					'forupdate' => true
				)
			);

			$queue = new Zend_Queue('Db', $options);

			if ($params['id'] == '_new') {
				$queue->send('ping');
				$count = $queue->count();
				if($count == 1) {
					$messages = $queue->receive(1);
					foreach($messages as $message) {
						$queue->deleteMessage($message);
					}
				}
			}

			$tmp = array();
			$instance = $config->instance;
			unset($config->instance);

			$tmp[$instance] = $config->toArray();
			$tmp['config']['instance'] = $instance;
			$tmp[$instance]['queue'][$params['queue-name']] = $options;

			$newConfig = new Zend_Config($tmp);
			$writer = new Zend_Config_Writer_Ini(array(
				'config'   => $newConfig,
				'filename' => $configPath
			));
			$writer->write();

			$status = true;
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

	public function editAction() {
		$config = Ini_Config::getInstance();
		$isNew = false;
		$queue = array();
		$messageCount = 0;

		$request = $this->getRequest();
		$request->setParamSources(array('_GET'));

		$queueId = $request->getParam('queueId');
		if (empty($queueId)) {
			$queueId = '_new';
		}

		$account = $this->_helper->GetRequestedAccount();
		$accountId = $account->id;

		if ($queueId == '_new') {
			$isNew = true;
		} else {
			$queue = $config->queue->get($queueId)->toArray();
			$obj = new Zend_Queue('Db', $queue);
			$messageCount = $obj->count();
		}

		$this->view->assign(array(
			'referer' => @$_SERVER['HTTP_REFERER'],
			'id' => $queueId,
			'queue' => $queue,
			'isNew' => $isNew,
			'messageCount' => $messageCount
		));
	}
}

?>
