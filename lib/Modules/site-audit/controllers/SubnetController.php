<?php

/**
* @author Tim Rupp
*/
class SiteAudit_SubnetController extends Zend_Controller_Action {
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
		$permissions = new Permissions;
		$status = false;
		$message = null;

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$params = $request->getParams();
		$network = $request->getParam('network');
		$netmask = $request->getParam('netmask');

		try {
			if (!Ip::isIpAddress($network)) {
				throw new Exception('Provided network must be an IP Address');
			} else if (!is_numeric($netmask)) {
				throw new Exception('Provided netmask must be a number');
			}

			$subnet = sprintf('%s/%s', $network, $netmask);
			$session->siteAudit['subnet'] = $subnet;

			$status = true;
		} catch (Exception $error) {
			$log->err($error->getMessage());
			$status = false;
			$message = 'Failed to save the specified subnet';
		}

		$this->view->response = array(
			'status' => $status,
			'message' => $message
		);
	}
}

?>
