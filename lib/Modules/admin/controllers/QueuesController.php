<?php

/**
* @author Tim Rupp
*/
class Admin_QueuesController extends Zend_Controller_Action {
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

	public function indexAction() {
		$this->view->assign(array(
			'page' => 1
		));
	}

	public function deleteAction() {
		$status = false;
		$message = null;

		$configPath = sprintf('%s/etc/local/config.conf', _ABSPATH);

		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));
		$id = $request->getParam('id');

		$options = $config->queue->get($id);

		try {
			if (empty($options)) {
				$db = App_Db::getInstance($config->database->default);
				$log->info(sprintf('Weird. the queue name "%s" was not found in the config file. It may have manually been removed or wasn\'t created properly in the first place. Removing it from the database', $id));

				$where = $db->quoteInto('queue_name = ?', $id);
				$result = $db->delete('queue', $where);

				if ($result > 0) {
					$status = true;
				} else {
					throw new Exception('An unknown error occurred while trying to remove the orphaned queue database entry');
				}
			} else {
				if ($options->options->forupdate) {
					$options->options->forupdate = true;
				} else {
					$options->options->forupdate = false;
				}

				$queue = new Zend_Queue('Db', $options->toArray());

				$result = $queue->deleteQueue();
				$status = true;
			}

			$tmp = array();
			$instance = $config->instance;
			unset($config->instance);

			$tmp[$instance] = $config->toArray();
			$tmp['config']['instance'] = $instance;
			unset($tmp[$instance]['queue'][$id]);

			$newConfig = new Zend_Config($tmp);
			$writer = new Zend_Config_Writer_Ini(array(
				'config'   => $newConfig,
				'filename' => $configPath
			));
			$writer->write();
		} catch (Exception $error) {
			$status = false;
			$message = $error->getMessage();
		}

		$this->view->response = array(
			'status' => $status,
			'message' => $message
		);
	}

	public function searchAction() {
		$results = array();
		$limit = 15;

		$log = App_Log::getInstance(self::IDENT);
		$request = $this->getRequest();
		$request->setParamSources(array('_GET'));

		try {
			$account = $this->_helper->GetRequestedAccount();

			if (!empty($account->settings->limitQueues)) {
				$limit = $account->settings->limitQueues;
			}

			$page = $request->getParam('page');

			if (empty($page)) {
				$page = 1;
			}

			$bundle = new Bundle_Queue();
			$bundle->page($page);
			$bundle->limit($limit);

			$results = $bundle->get();
			$totalQueues = $bundle->count(false);
			$totalPages = ceil($totalQueues / $limit);

			$this->view->assign(array(
				'results' => $results
			));

			$message = $this->view->render('queues/search-results.phtml');
			$status = true;
			$this->view->clearVars();

			$response['totalQueues'] = $totalQueues;
			$response['totalPages'] = $totalPages;
			$response['currentPage'] = $page;
		} catch (Exception $error) {
			$status = false;
			$message = $error->getMessage();
			$log->err($message);
		}

		$response['status'] = $status;
		$response['message'] = $message;

		$this->view->response = $response;
	}
}

?>
