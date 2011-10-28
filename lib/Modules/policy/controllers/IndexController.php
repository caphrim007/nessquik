<?php

/**
* @author Tim Rupp
*/
class Policy_IndexController extends Zend_Controller_Action {
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
		$account = $this->_helper->GetRequestedAccount();

		$this->view->assign(array(
			'page' => 1,
			'account' => $account
		));
	}

	public function searchAction() {
		$status = false;
		$message = null;
		$results = array();
		$response = array();
		$limit = 15;

		$log = App_Log::getInstance(self::IDENT);

		try {
			$request = $this->getRequest();
			$request->setParamSources(array('_GET'));

			$account = $this->_helper->GetRequestedAccount();

			if (!empty($account->settings->limitPolicies)) {
				$limit = $account->settings->limitPolicies;
			}

			$page = $request->getParam('page');
			$policyName = $request->getParam('policyName');

			if (empty($page)) {
				$page = 1;
			}

			$bundle = new Bundle_Policy();
			$bundle->page($page);
			$bundle->limit($limit);
			$bundle->ownerId($account->id);

			if (!empty($policyName)) {
				$bundle->name($policyName);
			}

			$results = $bundle->get();
			$totalPolicies = $bundle->count(false);
			$totalPages = ceil($totalPolicies / $limit);

			$this->view->assign(array(
				'account' => $this->session,
				'limit' => $limit,
				'page' => $page,
				'results' => $results
			));
			$message = $this->view->render('index/search-results.phtml');
			$this->view->clearVars();

			$response['totalPolicies'] = $totalPolicies;
			$response['totalPages'] = $totalPages;
			$response['currentPage'] = $page;

			$status = true;
		} catch (Exception $error) {
			$status = false;
			$message = $error->getMessage();
			$log->err($message);
		}

		$response['status'] = $status;
		$response['message'] = $message;

		$this->view->response = $response;
	}

	public function deleteAction() {
		$status = false;
		$message = null;

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));
		$policyId = $request->getParam('policyId');

		$account = $this->_helper->GetRequestedAccount();

		if ($account->policy->hasPolicy($policyId)) {
			$status = $account->policy->delete($policyId);
		} else {
			$status = false;
			$message = 'You do not have permission to delete this policy';
		}

		$this->view->response = array(
			'status' => $status,
			'message' => $message
		);
	}
}

?>
