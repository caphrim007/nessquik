<?php

/**
* @author Tim Rupp
*/
class Admin_AuditController extends Zend_Controller_Action {
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

		if (!$this->session->acl->isAllowed('Capability', array('admin_operator', 'edit_audit'))) {
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

		$log = App_Log::getInstance(self::IDENT);

		$account = $this->_helper->GetRequestedAccount();
		$accountId = $account->id;

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));
		$auditId = $request->getParam('auditId');

		if ($this->_helper->CanChangeAudit($accountId, $auditId)) {
			$audit = new Audit($auditId);
			$status = $audit->delete($auditId);
		} else {
			$status = false;
			$message = 'You do not have permission to delete this audit';
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

			if (!empty($account->settings->limitAudits)) {
				$limit = $account->settings->limitAudits;
			}

			$page = $request->getParam('page');
			$auditStatus = $request->getParam('auditStatus');
			$owner = trim($request->getParam('owner'));

			if (empty($page)) {
				$page = 1;
			}

			$bundle = new Bundle_AuditAdmin();
			$bundle->page($page);
			$bundle->limit($limit);
			$bundle->status($auditStatus);

			if (!empty($owner)) {
				$bundle->owner($owner);
			}

			$results = $bundle->get();
			$totalAudits = $bundle->count(false);
			$totalPages = ceil($totalAudits / $limit);

			$this->view->assign(array(
				'account' => $this->session,
				'limit' => $limit,
				'page' => $page,
				'results' => $results,
				'status' => $auditStatus
			));

			$message = $this->view->render('audit/search-results.phtml');
			$status = true;
			$this->view->clearVars();

			$response['totalAudits'] = $totalAudits;
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
