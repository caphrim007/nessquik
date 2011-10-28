<?php

/**
* @author Tim Rupp
*/
class Metrx_IndexController extends Zend_Controller_Action {
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

		if (!$this->session->acl->isAllowed('Capability', 'admin_operator')) {
			$this->_redirector = $this->_helper->getHelper('Redirector');
			$this->_redirector->gotoSimple('permission-denied', 'error', 'default');
		}

		$this->view->assign(array(
			'action' => $this->_request->getActionName(),
			'config' => $config,
			'controller' => $this->_request->getControllerName(),
			'module' => $this->_request->getModuleName(),
			'session' => $this->session
		));
	}

	public function indexAction() {
		$tables = array();
		$page = 1;
		$limit = 15;

		$charts = $this->_helper->ListMetrx($page, $limit, 'charts');
		$tables = $this->_helper->ListMetrx($page, $limit, 'tables');
		$reports = $this->_helper->ListMetrx($page, $limit, 'reports');

		$this->view->assign(array(
			'charts' => $charts,
			'reports' => $reports,
			'tables' => $tables
		));
	}
}

?>
