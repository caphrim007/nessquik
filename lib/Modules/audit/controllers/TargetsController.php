<?php

/**
* @author Tim Rupp
*/
class Audit_TargetsController extends Zend_Controller_Action {
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

			$page = $request->getParam('page');
			$action = $request->getParam('doAction');
			$auditId = $request->getParam('auditId');

			if (empty($page)) {
				$page = 1;
			}

			$audit = new Audit($auditId);
			$results = $audit->target->getTargets($page, $limit, $action);
			$totalTargets = $audit->target->count($action);
			$totalPages = ceil($totalTargets / $limit);

			$this->view->assign(array(
				'results' => $results
			));

			$message = $this->view->render('targets/search-results.phtml');
			$status = true;
			$this->view->clearVars();

			$response['totalTargets'] = $totalTargets;
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

	public function removeAction() {
		$status = false;
		$message = null;

		$log = App_Log::getInstance(self::IDENT);

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$auditId = $request->getParam('auditId');
		$targetId = $request->getParam('targetId');

		try {
			$audit = new Audit($auditId);
			$result = $audit->target->removeTarget($targetId);
			$status = true;
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

	public function addAction() {
		$status = false;
		$message = null;
		$result = false;

		$log = App_Log::getInstance(self::IDENT);

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$auditId = $request->getParam('auditId');
		$target = $request->getParam('target');
		$type = $request->getParam('type');
		$doAction = $request->getParam('doAction');

		try {
			$audit = new Audit($auditId);
			$account = $this->_helper->GetRequestedAccount();
			$accountId = $account->id;

			$target = trim($target);

			switch($type) {
				case 'HostnameTarget':
					if (Ip::isIpAddress($target)) {
						$result = $this->_helper->CanAuditTarget($account, $target, 'NetworkTarget');
						if ($result === false) {
							$log->info('IP permission lookup was false, trying hostname');

							// May be allowed to scan the hostname but they specified the IP
							$hostname = gethostbyaddr($target);
							$log->debug(sprintf('Resolved hostname to %s', $hostname));

							// May be a host name
							$result = $this->_helper->CanAuditTarget($account, $hostname, 'HostnameTarget');
						}
					} else {
						$result = $this->_helper->CanAuditTarget($account, $target, 'HostnameTarget');
						if ($result === false) {
							$log->info('Hostname permission lookup was false, trying IP');

							// May be allowed to scan the IP, but they specified the hostname
							$ip = gethostbyname($target);
							$log->debug(sprintf('Resolved IP to %s', $ip));

							if (Ip::isIpAddress($ip)) {
								// May be an IP
								$result = $this->_helper->CanAuditTarget($account, $ip, 'NetworkTarget');
							}
						}
					}
					break;
				case 'ClusterTarget':
				case 'RangeTarget':
				case 'NetworkTarget':
					$result = $this->_helper->CanAuditTarget($account, $target, $type);
					break;
			}

			if ($result === false) {
				throw new Exception('You do not have permission to scan the specified target');
			}

			switch($doAction) {
				case 'include':
					$result = $audit->target->includeTarget($target, $type);
					break;
				case 'exclude':
					$result = $audit->target->excludeTarget($target, $type);
					break;
				default:
					throw new Exception('The specified action was unknown');
			}

			$status = true;
		} catch (Exception $error) {
			$status = false;
			$message = $error->getMessage();
			$log->err($message);
		}

		$this->view->response = array(
			'status' => $status,
			'message' => $message,
			'action' => $doAction
		);
	}
}

?>
