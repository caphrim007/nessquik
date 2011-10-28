<?php

/**
* @author Tim Rupp
*/
class Audit_PolicyController extends Zend_Controller_Action {
	public $session;

	const IDENT = __CLASS__;

	public function init() {
		parent::init();

		$config = Ini_Config::getInstance();
		$auth = Zend_Auth::getInstance();

		$sessionUser = $auth->getIdentity();
		$sessionId = Account_Util::getId($sessionUser);
		$this->session = new Account($sessionId);

		if ($this->session->isFirstBoot()) {
			$this->_redirector = $this->_helper->getHelper('Redirector');
			$this->_redirector->gotoSimple('index', 'index', 'start');
		}

		$contextSwitch = $this->_helper->getHelper('contextSwitch');
		$contextSwitch->addActionContext('search', 'json');
		$contextSwitch->addActionContext('remove', 'json');
		$contextSwitch->initContext();

		$this->view->assign(array(
			'action' => $this->_request->getActionName(),
			'config' => $config,
			'controller' => $this->_request->getControllerName(),
			'module' => $this->_request->getModuleName(),
			'session' => $this->session
		));
	}

	public function searchAction() {
		$isNew = false;
		$results = array();
		$limit = 15;
		$audit = null;
		$response = array();
		$status = false;
		$message = null;

		$log = App_Log::getInstance(self::IDENT);
		$request = $this->getRequest();
		$request->setParamSources(array('_GET'));

		try {
			$account = $this->_helper->GetRequestedAccount();

			if (!empty($account->settings->limitPolicies)) {
				$limit = $account->settings->limitPolicies;
			}

			$request = $this->getRequest();
			$request->setParamSources(array('_GET'));

			$auditId = $request->getParam('auditId');
			$currentPolicyId = $request->getParam('curPolicyId');
			$page = $request->getParam('page');
			if (empty($page)) {
				$page = 1;
			}

			$accountId = $account->id;

			if ($auditId == '_new') {
				$isNew = true;
			} else {
				$isNew = false;
				$audit = new Audit($auditId);
			}

			$results = $account->policy->getPolicies($page, $limit);
			$totalPolicies = $account->policy->count();
			$totalPages = ceil($totalPolicies / $limit);

			$this->view->assign(array(
				'account' => $this->session,
				'audit' => $audit,
				'curPolicyId' => $currentPolicyId,
				'isNew' => $isNew,
				'limit' => $limit,
				'page' => $page,
				'results' => $results
			));
			$message = $this->view->render('policy/search-results.phtml');
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
}

?>
