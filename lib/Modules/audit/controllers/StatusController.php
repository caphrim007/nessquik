<?php

/**
* @author Tim Rupp
*/
class Audit_StatusController extends Zend_Controller_Action {
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

		if (!$this->session->acl->isAllowed('Capability', 'edit_audit')) {
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
		$account = $this->_helper->GetRequestedAccount();

		$this->view->assign(array(
			'page' => 1,
			'account' => $account
		));
	}

	public function scannerJobsAction() {
		$results = array();
		$limit = 15;

		$config = Ini_Config::getInstance();
		$couch = new Phly_Couch($config->database->couch->params);

		$scanners = Audit_Server_Util::getServers(1,0);

		$documents = $couch->view('audit', 'scannersRunningAudits', array('group' => true));

		foreach($scanners as $scanner) {
			foreach($documents as $doc) {
				if ($doc->key == $scanner['id']) {
					$scanner['running'] = $doc->value;
				}
			}

			$results[] = $scanner;
		}

		$this->view->assign(array(
			'account' => $this->session,
			'results' => $results,
		));

		$content = $this->view->render('status/scanner-jobs-content.phtml');

		$this->view->clearVars();
		$this->view->response = array(
			'totalResults' => count($results),
			'content' => $content
		);
	}
}

?>
