<?php

/**
* @author Tim Rupp
*/
class Queues_MessagesController extends Zend_Controller_Action {
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

		if (!$this->session->acl->isAllowed('Capability', 'edit_queue')) {
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

	public function searchAction() {
		$results = array();
		$limit = 15;

		$account = $this->_helper->GetRequestedAccount();
		$limits = $account->doc->toArray();

		if (!empty($limits['limitMessages'])) {
			$limit = $limits['limitMessages'];
		}

		$request = $this->getRequest();
		$request->setParamSources(array('_GET'));

		$page = $request->getParam('page');
		$queueId = $request->getParam('queueId');

		if (empty($page)) {
			$page = 1;
		}

		$queue = new Queue($queueId);
		$results = $queue->getMessages($page, $limit);

		$this->view->assign(array(
			'limit' => $limit,
			'page' => $page,
			'results' => $results
		));
	}

	public function deleteAction() {
		$status = false;
		$message = null;

		$log = App_Log::getInstance(self::IDENT);

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$queueId = $request->getParam('queueId');
		$messageId = $request->getParam('messageId');

		try {
			$queue = new Queue($queueId);
			$status = $queue->deleteMessage($messageId);
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

	public function flushAction() {
		$status = false;
		$message = null;

		$log = App_Log::getInstance(self::IDENT);

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$queueId = $request->getParam('queueId');

		try {
			$queue = new Queue($queueId);
			$status = $queue->flush();
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
