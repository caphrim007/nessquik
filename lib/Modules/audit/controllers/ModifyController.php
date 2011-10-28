<?php

/**
* @author Tim Rupp
*/
class Audit_ModifyController extends Zend_Controller_Action {
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

		$contextSwitch = $this->_helper->getHelper('contextSwitch');
		$contextSwitch->addActionContext('search-policies', 'json');
		$contextSwitch->initContext();

		$this->view->assign(array(
			'action' => $request->getActionName(),
			'config' => $config,
			'controller' => $request->getControllerName(),
			'module' => $request->getModuleName(),
			'session' => $this->session
		));
	}

	public function editAction() {
		$isNew = false;
		$audit = null;
		$schedule = null;
		$scanners = array();

		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$today = new Zend_Date;
		$account = $this->_helper->GetRequestedAccount();
		$accountId = $account->id;

		$request = $this->getRequest();
		$request->setParamSources(array('_GET'));

		$auditId = $request->getParam('id');
		if (empty($auditId)) {
			$auditId = '_new';
		}

		if ($auditId == '_new') {
			$isNew = true;
			$auditId = Audit_Util::create();

			$permission = new Permissions;
			$permission = $permission->get('Audit', $auditId);

			$result = $account->acl->allow($permission[0]['permission_id']);
		}

		if ($this->_helper->CanChangeAudit($accountId, $auditId)) {
			$audit = new Audit($auditId);
		} else {
			$this->_redirector = $this->_helper->getHelper('Redirector');
			$this->_redirector->gotoSimple('permission-denied', 'error', 'default');
		}

		$tmp = $account->scanner->getScanners();
		if (empty($tmp)) {
			$scanners = array();
		} else {
			foreach($tmp as $scanner) {
				$scanners[] = new Audit_Server($scanner['scanner_id']);
			}
		}

		$this->view->assign(array(
			'audit' => $audit,
			'id' => $auditId,
			'isNew' => $isNew,
			'scanners' => $scanners,
			'today' => $today
		));
	}

	public function saveAction() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$filter = new Zend_Filter_Boolean(Zend_Filter_Boolean::ALL);

		$included = array();
		$excluded = array();
		$totalIncludes = 0;

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$account = $this->_helper->GetRequestedAccount();
		$accountId = $account->id;

		$auditId = $request->getParam('auditId');
		$policyId = $request->getParam('policyId');
		$curPolicyId = $request->getParam('curPolicyId');
		$isNew = $request->getParam('isNew');
		$auditName = $request->getParam('name');
		$scannerId = $request->getParam('scanner');
		$hasScheduling = $request->getParam('scheduling');

		if (empty($auditId)) {
			throw new Zend_Controller_Action_Exception('The audit ID provided to the controller was empty');
		}

		$audit = new Audit($auditId);

		if ($this->_helper->CanChangeAudit($accountId, $auditId)) {
			if (empty($policyId) && empty($curPolicyId)) {
				$policyId = 0;
			} else if (empty($policyId)) {
				$policyId = $curPolicyId;
			}
		} else {
			$this->_redirector = $this->_helper->getHelper('Redirector');
			$this->_redirector->gotoSimple('permission-denied', 'error', 'default');
		}

		try {
			if ($policyId != 0) {
				if ($account->policy->hasPolicy($policyId)) {
					$audit->policy = $policyId;
				} else {
					throw new Zend_Controller_Action_Exception('You do not have permission to use this policy');
				}
			}

			$audit->name = $auditName;
			$audit->scanner = $scannerId;
			$audit->scheduling = $filter->filter($hasScheduling);
			$audit->last_modified = new Zend_Date;

			//$audit->setSchedule($params);
			//$audit->setNotification($params);

			$result = $audit->update();

			$status = true;
			$message = 'Successfully made the appropriate changes to the audit';
		} catch (Exception $error) {
			$log->err($error->getMessage());
			$status = false;
			$message = 'Failed to make the changes to the audit';
		}

		$this->view->response = array(
			'status' => $status,
			'message' => $message
		);
	}

	public function cancelAction() {
		$status = false;
		$message = null;

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$auditId = $request->getParam('auditId');
		if (empty($auditId)) {
			throw new Zend_Controller_Action_Exception('The audit ID provided to the controller was empty');
		}

		$account = $this->_helper->GetRequestedAccount();
		$accountId = $account->id;

		if ($this->_helper->CanChangeAudit($accountId, $auditId)) {
			$audit = new Audit($auditId);
		} else {
			$this->_redirector = $this->_helper->getHelper('Redirector');
			$this->_redirector->gotoSimple('permission-denied', 'error', 'default');
		}

		$this->view->response = array(
			'status' => $result,
			'message' => $message
		);
	}

	public function searchReportsAction() {
		$results = array();
		$limit = 15;

		$request = $this->getRequest();
		$request->setParamSources(array('_GET'));

		$auditId = $request->getParam('auditId');
		$page = $request->getParam('page');
		if (empty($page)) {
			$page = 1;
		}

		$account = $this->_helper->GetRequestedAccount();
		$accountId = $account->id;

		if ($this->_helper->CanChangeAudit($accountId, $auditId)) {
			$audit = new Audit($auditId);
			$results = $audit->report->getReports($page, $limit);
		}

		$results = array_reverse($results);

		$this->view->assign(array(
			'limit' => $limit,
			'page' => $page,
			'results' => $results
		));
	}
}

?>
