<?php

/**
* @author Tim Rupp
*/
class Admin_ScannersController extends Zend_Controller_Action {
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

		if (!$this->session->acl->isAllowed('Capability', array('admin_operator', 'edit_scanner'))) {
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

		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));
		$id = $request->getParam('scannerId');

		try {
			$server = new Audit_Server($id);
		} catch (Zend_Exception $error) {
			$log->err($error->getMessage());
		}

		$status = $server->delete();

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
			$account = $this->_helper->GetRequestedAccount();

			if (!empty($account->settings->limitScanners)) {
				$limit = $account->settings->limitScanners;
			}

			$request = $this->getRequest();
			$request->setParamSources(array('_GET'));

			$page = $request->getParam('page');

			if (empty($page)) {
				$page = 1;
			}

			$bundle = new Bundle_AuditServer();
			$bundle->page($page);
			$bundle->limit($limit);
			$results = $bundle->get();
			$totalScanners = $bundle->count();
			$totalPages = ceil($totalScanners / $limit);

			$this->view->assign(array(
				'limit' => $limit,
				'page' => $page,
				'results' => $results
			));

			$status = true;
			$message = $this->view->render('scanners/search-results.phtml');
			$this->view->clearVars();

			$response['totalScanners'] = $totalScanners;
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
