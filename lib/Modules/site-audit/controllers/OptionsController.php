<?php

/**
* @author Tim Rupp
*/
class SiteAudit_OptionsController extends Zend_Controller_Action {
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

		if (!$this->session->acl->isAllowed('Capability', 'admin_operator')) {
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
		$session = Zend_Registry::get('nessquik');
		$config = Ini_Config::getInstance();
		$couch = new Phly_Couch($config->database->couch->params);
		$status = true;
		$message = null;

		$account = new Account($session->siteAudit['accountId']);

		$tmp = $account->scanner->getScanners();
		if (empty($tmp)) {
			$scanners = array();
		} else {
			foreach($tmp as $scannerInfo) {
				$scanners[] = new Audit_Server($scannerInfo['id']);
			}
		}

		$types = Authentication_Util::authTypes();
		$this->view->assign(array(
			'types' => $types,
			'scanners' => $scanners
		));
	}

	public function saveAction() {
		$session = Zend_Registry::get('nessquik');
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$filter = new Zend_Filter_Boolean(Zend_Filter_Boolean::ALL);
		$auNotify = new Audit_Notification;
		$status = false;
		$message = null;

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$params = $request->getParams();

		$account = new Account($session->siteAudit['accountId']);
		$policyId = $this->getPolicyId();

		try {
			if ($account->policy->hasPolicy($policyId)) {
				$audit->policy = $policyId;
			} else {
				throw new Zend_Controller_Action_Exception('You do not have permission to use this policy');
			}

			if (empty($params['scanner'])) {
				$base = $request->getBaseUrl();

				$url = sprintf('%s/admin/roles/edit?id=%s', $base, $account->primary_role);
				$mesg = sprintf('You need to choose a scanner. If you do not have access to any. Grant that access <a href="%s" class="hypertext">now</a>.', $url);
				throw new Exception_NoScannerAccess($mesg);
			}

			$session->siteAudit['policyId'] = $policyId;
			$session->siteAudit['scannerId'] = $params['scanner'];
			$session->siteAudit['notifications'] = $auNotify->parse($params);
			$status = true;
		} catch (Exception_NoScannerAccess $error) {
			$log->err($error->getMessage());
			$status = false;
			$message = $error->getMessage();
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

	protected function getPolicyId() {
		$session = Zend_Registry::get('nessquik');
		$account = new Account($session->siteAudit['accountId']);

		$policies = $account->policy->getPolicies(1,1);
		return $policies[0]['id'];
	}
}

?>
