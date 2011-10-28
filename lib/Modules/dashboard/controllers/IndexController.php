<?php

/**
* @author Tim Rupp
*/
class Dashboard_IndexController extends Zend_Controller_Action {
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

	public function indexAction() {
		$hasAudits = $this->session->audit->hasAudits();

		$this->view->assign(array(
			'hasAudits' => $hasAudits,
			'parked' => $this->session->audit->count('N'),
			'pending' => $this->session->audit->count('P'),
			'running' => $this->session->audit->count('R'),
			'finished' => $this->session->audit->count('F')
		));
	}

	public function upcomingAction() {
		$status = false;
		$results = array();

		$request = $this->getRequest();
		$request->setParamSources(array('_GET'));

		// Dates are sent to us in milliseconds, so convert them to seconds
		$startTs = $request->getParam('start') / 1000;
		$start = new Zend_Date($startTs, Zend_Date::TIMESTAMP);

		$endTs = $request->getParam('end') / 1000;
		$end = new Zend_Date($endTs, Zend_Date::TIMESTAMP);

		if (isset($this->session->doc->upcomingAudits)) {
			$results = $this->session->doc->upcomingAudits;
		} else {
			$results = array();
		}

		$status = true;

		$this->view->response = array(
			'status' => $status,
			'results' => $results
		);
	}
}

?>
