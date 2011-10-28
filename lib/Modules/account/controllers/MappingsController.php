<?php

/**
* @author Tim Rupp
*/
class Account_MappingsController extends Zend_Controller_Action {
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

		$this->view->assign(array(
			'action' => $request->getActionName(),
			'config' => $config,
			'controller' => $request->getControllerName(),
			'module' => $request->getModuleName(),
			'session' => $this->session
		));
	}

	public function indexAction() {
		$request = $this->getRequest();
		$request->setParamSources(array('_GET'));

		$accountId = $request->getParam('accountId');

		if (!is_numeric($accountId)) {
			throw new Exception('The specified account ID is invalid');
		}

		$account = new Account($accountId);
		$mappings = $account->getMappings();

		$this->view->assign(array(
			'accountId' => $accountId,
			'account' => $account,
			'session' => $this->session
		));
	}

	public function createAction() {
		$request = $this->getRequest();
		$request->setParamSources(array('_GET'));

		$accountId = $request->getParam('accountId');

		if (!is_numeric($accountId)) {
			throw new Exception('The specified account ID is invalid');
		}

		$account = new Account($accountId);

		$this->view->assign(array(
			'accountId' => $accountId,
			'account' => $account,
			'session' => $this->session
		));
	}

	public function saveAction() {
		$status = false;
		$message = null;

		$log = App_Log::getInstance(self::IDENT);

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$accountId = $request->getParam('accountId');
		$mapName = $request->getParam('map-name');

		if (!is_numeric($accountId)) {
			throw new Exception('The specified account ID is invalid');
		}

		$account = new Account($accountId);
		$status = $account->createAccountMapping($mapName);

		if ($status === false) {
			$message = 'Failed to create the new account mapping';
		}

		$this->view->response = array(
			'status' => $status,
			'message' => $message
		);
	}

	public function deleteAction() {
		$status = false;
		$message = null;

		$log = App_Log::getInstance(self::IDENT);

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$accountId = $request->getParam('accountId');
		$mapId = $request->getParam('mapId');

		try {
			if (!is_numeric($accountId)) {
				throw new Zend_Controller_Action_Exception('The specified account ID is invalid');
			}

			if (!is_numeric($mapId)) {
				throw new Zend_Controller_Action_Exception('The specified map ID is invalid');
			}

			$account = new Account($accountId);
			$status = $account->deleteAccountMapping($mapId);
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

	public function searchAction() {
		$status = false;
		$message = null;
		$results = array();
		$limit = 15;

		try {
			$request = $this->getRequest();
			$request->setParamSources(array('_GET'));
			$page = $request->getParam('page');

			$account = $this->_helper->GetRequestedAccount();
			$limits = $account->doc->toArray();

			if (!empty($limits['limitMappings'])) {
				$limit = $limits['limitMappings'];
			}

			$results = $account->getMappings($page, $limit);

			if (empty($page)) {
				$page = 1;
			}

			$this->view->assign(array(
				'account' => $this->session,
				'limit' => $limit,
				'page' => $page,
				'results' => $results
			));

			$status = true;
			$message = $this->view->render('mappings/search-results.phtml');
			$this->view->clearVars();
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
