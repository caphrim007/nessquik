<?php

/**
* @author Tim Rupp
*/
class Admin_RolesController extends Zend_Controller_Action {
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

		if (!$this->session->acl->isAllowed('Capability', array('admin_operator', 'edit_role'))) {
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

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));
		$id = $request->getParam('roleId');

		$role = new Role($id);

		try {
			$result = $role->delete();
			$status = true;
		} catch (Role_Exception $error) {
			$status = false;
			$message = $error->getMessage();

			$log->err($error->getMessage());
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

		$log = App_Log::getInstance(self::IDENT);
		$request = $this->getRequest();
		$request->setParamSources(array('_GET'));

		try {
			$account = $this->_helper->GetRequestedAccount();

			if (!empty($account->settings->limitRoles)) {
				$limit = $account->settings->limitRoles;
			}

			$page = $request->getParam('page');
			$filter = $request->getParam('filter');

			if (empty($page)) {
				$page = 1;
			}

			$bundle = new Role_Bundle;
			$bundle->page($page);
			$bundle->limit($limit);

			if (!empty($filter)) {
				$filters = explode(' ', $filter);
				foreach($filters as $filter) {
					$bundle->filter($filter);
				}
			}

			$results = $bundle->get();
			$totalRoles = $bundle->count();
			$totalPages = ceil($totalRoles / $limit);

			$this->view->assign(array(
				'account' => $this->session,
				'limit' => $limit,
				'page' => $page,
				'results' => $results
			));

			$message = $this->view->render('roles/search-results.phtml');
			$status = true;
			$this->view->clearVars();

			$response['totalRoles'] = $totalRoles;
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
