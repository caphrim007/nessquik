<?php

/**
* @author Tim Rupp
*/
class Audit_IndexController extends Zend_Controller_Action {
	public $session;

	protected $_log;
	protected $_config;

	const IDENT = __CLASS__;

	public function init() {
		parent::init();

		$this->_config = Ini_Config::getInstance();
		$this->_log = App_Log::getInstance(self::IDENT);

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
			'config' => $this->_config,
			'controller' => $request->getControllerName(),
			'module' => $request->getModuleName(),
			'session' => $this->session
		));
	}

	public function indexAction() {
		$account = $this->_helper->GetRequestedAccount();
		$accountId = $account->id;

		$results = $this->_helper->RemoveOldGeneratedReports($accountId);

		$hasPolicies = $account->policy->hasPolicies();
		if ($hasPolicies === false) {
			$this->_redirector = $this->_helper->getHelper('Redirector');
			$this->_redirector->gotoSimple('index', 'index', 'policy');
		}

		$this->view->assign(array(
			'page' => 1,
			'account' => $account
		));
	}

	public function searchAction() {
		$results = array();
		$limit = 15;
		$status = false;
		$message = null;
		$response = array();

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

			if (empty($page)) {
				$page = 1;
			}

			$bundle = new Bundle_Audit();
			$bundle->page($page);
			$bundle->limit($limit);
			$bundle->status($auditStatus);
			$bundle->ownerId($account->id);

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

			$message = $this->view->render('index/search-results.phtml');
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

	public function deleteAction() {
		$status = false;
		$message = null;

		$account = $this->_helper->GetRequestedAccount();

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));
		$auditId = $request->getParam('auditId');

		try {
			if ($account->audit->hasAudit($auditId)) {
				$audit = new Audit($auditId);
				$status = $account->audit->delete($auditId);
			} else {
				$status = false;
				$message = 'You do not have permission to delete this audit';
			}
		} catch (Exception $error) {
			$status = false;
			$message = $error->getMessage();
			$this->_log->err($message);
		}

		$this->view->response = array(
			'status' => $status,
			'message' => $message
		);
	}

	public function startAction() {
		$status = false;
		$message = null;

		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$account = $this->_helper->GetRequestedAccount();
		$accountId = $account->id;

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));
		$auditId = $request->getParam('auditId');
		$runWhen = $request->getParam('runWhen');
		$now = new Zend_Date;
		$shouldStart = false;
		$started = false;
		$exceptionOccurred = false;
		$loopCounter = 0;

		try {
			if ($account->audit->hasAudit($auditId)) {
				$audit = new Audit($auditId);

				switch($runWhen) {
					case 'future':
						$newSchedule = $request->getParam('dateScheduled');
						$dateScheduled = new Zend_Date;

						$dateScheduled = $dateScheduled->setDate($newSchedule, 'yyyy-MM-ddTHH:mm:ss');
						$dateScheduled = $dateScheduled->setTime($newSchedule, 'yyyy-MM-ddTHH:mm:ss');

						if ($dateScheduled->isEarlier($now)) {
							/**
							* If a user sent us a time in the past, then just schedule
							* the scan immediately
							*/
							$dateScheduled = new Zend_Date;
							$audit->date_scheduled = $dateScheduled;
							$shouldStart = true;
						} else {
							$audit->date_scheduled = $dateScheduled;
							$audit->status = 'P';
							$audit->update();
						}

						break;
					case 'schedule':
						$schedule = $audit->schedule->enumerateFutureSchedule(1);
						$dateScheduled = new Zend_Date($schedule[0], Zend_Date::W3C);

						if ($dateScheduled->isEarlier($now)) {
							/**
							* If a user sent us a time in the past, then just schedule
							* the scan immediately
							*/
							$dateScheduled = new Zend_Date;
							$audit->date_scheduled = $dateScheduled;
							$shouldStart = true;
						} else {
							$audit->date_scheduled = $dateScheduled;
							$audit->status = 'P';
							$audit->update();
							
						}

						break;
					case 'now':
					default:
						$dateScheduled = new Zend_Date;
						$audit->date_scheduled = $dateScheduled;
						$shouldStart = true;
				}

				$audit->start();

				$log->debug(sprintf('User "%s" started audit with ID "%s" from UI', $account->username, $audit->id));
				$this->_notifyDashboardRebuild($account->id);
				$status = true;
			} else {
				throw new Exception('You do not have permission to start this audit');
			}
		} catch (Exception $error) {
			$status = false;
			$message = 'An unknown error occurred';
			$log->err($error->getMessage());
		}

		$this->view->response = array(
			'status' => $status,
			'message' => $message
		);
	}

	public function stopAction() {
		$status = false;
		$message = null;

		$account = $this->_helper->GetRequestedAccount();
		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));
		$auditId = $request->getParam('auditId');

		if ($account->audit->hasAudit($auditId)) {
			$audit = new Audit($auditId);
			$audit->account = $account;
			$audit->stop();

			$status = true;
		} else {
			$status = false;
			$message = 'You do not have permission to stop this audit';
		}

		$this->view->response = array(
			'status' => $status,
			'message' => $message
		);
	}

	public function parkAction() {
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

		$audit->status = 'N';
		$audit->update();
		$status = true;

		$this->view->response = array(
			'status' => $status,
			'message' => $message
		);
	}

	protected function _notifyDashboardRebuild($accountId) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);

		$queue = $config->queue->get('rebuild-upcoming-audits');
		if ($queue->options->forupdate) {
			$queue->options->forupdate = true;
		} else {
			$queue->options->forupdate = false;
		}
		$queue = new Zend_Queue('Db', $queue->toArray());
		$message = array(
			'accountId' => $accountId,
		);
		$message = json_encode($message);
		$queue->send($message);
		$log->debug('Sent message to queue');
	}
}

?>
