<?php

/**
* @author Tim Rupp
*/
class Audit_ReportController extends Zend_Controller_Action {
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

		$this->view->assign(array(
			'action' => $this->_request->getActionName(),
			'config' => $config,
			'controller' => $this->_request->getControllerName(),
			'module' => $this->_request->getModuleName(),
			'session' => $this->session
		));
	}

	public function fetchAction() {
		$status = false;
		$message = null;

		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);

		$request = $this->getRequest();
		$request->setParamSources(array('_GET'));

		$reportId = $request->getParam('reportId');

		$account = $this->_helper->GetRequestedAccount();
		$accountId = $account->id;

		$report = new Audit_Report_Object($reportId);
		$view = new Audit_Report_View($report);

		try {
			$status = true;
			$message = $view->render('htmlv2-inline');
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

	public function downloadAction() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);

		$request = $this->getRequest();
		$request->setParamSources(array('_GET'));

		$format = $request->getParam('format');
		$reportId = $request->getParam('reportId');
		$auditId = $request->getParam('auditId');

		$account = $this->_helper->GetRequestedAccount();
		$accountId = $account->id;
		$date = new Zend_Date;

		if ($this->_helper->CanChangeAudit($accountId, $auditId)) {
			$audit = new Audit($auditId);
		}

		try {
			$report = new Audit_Report_Object($reportId);
			if ($report === false) {
				throw new Zend_Controller_Action_Exception('The specified report was not found in this audit');
			} else {
				$view = new Audit_Report_View($report);
				switch($format) {
					case 'nessus':
						$filename = 'report.nessus';
						$data = App_Log_Formatter_Default::removeWhitespace($view->render($format));
						break;
					case 'nbe':
						$filename = 'report.nbe';
						$data = $view->render($format);
						break;
					case 'txt':
						$filename = 'report.txt';
						$data = $view->render($format);
						break;
					default:
					case 'html':
						$filename = 'report.html';
						$data = App_Log_Formatter_Default::removeWhitespace($view->render($format));
						break;
				}

			}
		} catch (Exception $error) {
			$data = $error->getMessage();
			$filename = 'error.txt';
		}

		$options = array(
			'modified' => $date->get(Zend_Date::TIMESTAMP),
			'disposition' => 'attachment',
			'cache' => array(
				'must-revalidate' => true,
				'no-store' => true
			)
		);

		Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->setNoRender(true);
		$this->_helper->sendFile->sendData($data, 'text/plain', $filename, $options);
	}

	public function generateAction() {
		set_time_limit(0);

		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$format = $request->getParam('format');
		$audits = $request->getParam('auditIds');

		$account = $this->_helper->GetRequestedAccount();
		$accountId = $account->id;
		$date = new Zend_Date;
		$data = null;
		$message = null;

		if ($audits == 'all') {
			$audits = $account->audit->getAudits(null, null, 'F');
			if (empty($audits)) {
				$audits = array();
			} else {
				foreach($audits as $audit) {
					$tmp[] = $audit['id'];
				}
				$audits = $tmp;
			}
		} else if (!is_array($audits)) {
			$audits = explode(',', $audits);
		}

		try {
			if (empty($audits)) {
				throw new Exception('No audits were selected for download');
			}

			switch($format) {
				case 'nessus':
					$filename = 'report.nessus';
					break;
				case 'nbe':
					$filename = 'report.nbe';
					break;
				case 'txt':
					$filename = 'report.txt';
					break;
				default:
				case 'html':
					$filename = 'report.html';
					break;
			}

			$tmpDir = sprintf('%s/tmp/priv', _ABSPATH);
			$tmpFile = sprintf('%s/tmp/priv/%s.%s', _ABSPATH, $accountId, $filename);
			if (!is_dir($tmpDir)) {
				throw new Exception('The temporary priv directory was not found');
			}

			if (!is_writable($tmpDir)) {
				throw new Exception('The temporary priv directory is not writable');
			}

			file_put_contents($tmpFile, '');

			foreach($audits as $auditId) {
				if ($this->_helper->CanChangeAudit($accountId, $auditId)) {
					$audit = new Audit($auditId);
				} else {
					$log->info(sprintf('Account with ID "%s" is not allowed to download reports for audit with ID "%s"', $accountId, $auditId));
					continue;
				}

				$audit = new Audit($auditId);
				$reportId = $audit->report->getMostRecentReport();
				$report = new Audit_Report_Object($reportId);
				if ($report === false) {
					throw new Zend_Controller_Action_Exception('The specified report was not found in this audit');
				} else {
					$view = new Audit_Report_View($report);

					$data = $view->render($format);
					$result = file_put_contents($tmpFile, $data, FILE_APPEND);
				}
			}

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

	public function downloadSelectedAction() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$date = new Zend_Date;

		$account = $this->_helper->GetRequestedAccount();
		$accountId = $account->id;

		$request = $this->getRequest();
		$request->setParamSources(array('_GET'));

		$format = $request->getParam('format');

		switch($format) {
			case 'nessus':
				$filename = 'report.nessus';
				break;
			case 'nbe':
				$filename = 'report.nbe';
				break;
			case 'txt':
				$filename = 'report.txt';
				break;
			default:
			case 'html':
				$filename = 'report.html';
				break;
		}

		$tmpFile = sprintf('%s/tmp/priv/%s.%s', _ABSPATH, $accountId, $filename);

		if (file_exists($tmpFile)) {
			$data = file_get_contents($tmpFile);
		} else {
			$data = 'File not found';
		}

		$options = array(
			'modified' => $date->get(Zend_Date::TIMESTAMP),
			'disposition' => 'attachment',
			'cache' => array(
				'must-revalidate' => true,
				'no-store' => true
			)
		);

		Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->setNoRender(true);
		$this->_helper->sendFile->sendData($data, 'text/plain', $filename, $options);
	}

	public function emailAction() {
		$status = false;
		$message = null;

		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$filter = new Zend_Filter_Boolean(Zend_Filter_Boolean::ALL);
		$filtered = false;

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$auditId = $request->getParam('auditId');
		$reportId = $request->getParam('reportId');
		$params = $request->getParams();

		$account = $this->_helper->GetRequestedAccount();
		$accountId = $account->id;

		try {
			if ($this->_helper->CanChangeAudit($accountId, $auditId)) {
				$audit = new Audit($auditId);
			} else {
				throw new Exception('You do not have permission to use this audit');
			}

			$name = $account->proper_name;
			$email = $account->doc->emailContact;

			if (empty($email)) {
				throw new Exception('No email addresses were found for your account');
			}

			$params['sendFromName'] = $name;
			$params['sendFromAddr'] = $email[0];
			$params['recipients'] = $this->_helper->FilterBogusAddress($params['recipients']);

			if (empty($recipients) && $filter->filter($params['sendToOthers']) === true) {
				throw new Exception("You asked us to send this report to other people, but you did not specify any other recipients");
			} else if (!$filter->filter($params['sendToOthers']) && !$filter->filter($params['sendToMe'])) {
				throw new Exception("You chose to send the report to neither yourself or a list of recipients. That's not very useful ");
			}

			$status = $audit->report->sendReport($reportId, $params);
			$message = 'Successfully sent report to recipients';
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

	public function deleteAction() {
		$status = false;
		$message = null;

		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$filter = new Zend_Filter_Boolean(Zend_Filter_Boolean::ALL);
		$filtered = false;

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$auditId = $request->getParam('auditId');
		$reportId = $request->getParam('reportId');
		$params = $request->getParams();

		$account = $this->_helper->GetRequestedAccount();
		$accountId = $account->id;

		try {
			if ($this->_helper->CanChangeAudit($accountId, $auditId)) {
				$audit = new Audit($auditId);
			} else {
				throw new Exception('You do not have permission to use this audit');
			}

			$status = $audit->report->delete($reportId);
			$message = 'Successfully deleted report';
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

	public function deleteGeneratedAction() {
		$status = false;
		$message = null;

		$log = App_Log::getInstance(self::IDENT);

		try {
			$account = $this->_helper->GetRequestedAccount();
			$accountId = $account->id;

			$log->debug('Deleting old audit reports');
			$results = $this->_helper->RemoveOldGeneratedReports($accountId);

			$status = true;
			$message = null;
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
