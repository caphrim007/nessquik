<?php

/**
* @author Tim Rupp
*/
class SiteAudit_ExcludeController extends Zend_Controller_Action {
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

	}

	public function saveAction() {
		$session = Zend_Registry::get('nessquik');
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$excludedTargets = array();
		$status = false;
		$message = null;

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$target = $request->getParam('target');

		try {
			$targetList = $this->_helper->ListToArray($target);
			$targetExtract = $this->_helper->IpExtractToArray($target);
			$targets = array_unique(array_merge($targetList, $targetExtract));

			foreach($targets as $target) {
				if (!Ip::isIpAddress($target)) {
					continue;
				}

				$type = $this->_helper->DetermineTargetType($target);
				$excludedTargets[] = array(
					'target' => $target,
					'type' => $type
				);
			}

			$session->siteAudit['excluded'] = $excludedTargets;
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
