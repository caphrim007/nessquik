<?php

/**
* @author Tim Rupp
*/
class SiteAudit_IndexController extends Zend_Controller_Action {
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

	public function cancelAction() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$couch = new Phly_Couch($config->database->couch->params);
		$session = Zend_Registry::get('nessquik');
		$status = false;
		$message = null;

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$params = $request->getParams();
		$docId = $request->getParam('docId');

		try {
			$accountId = $session->siteAudit['accountId'];
			$roleId = $session->siteAudit['roleId'];

			if (!is_numeric($accountId)) {
				$log->info('Account ID was not a number, maybe it was already removed');
			} else {
				$account = new Account($accountId);
				$account->delete();
			}

			if (!is_numeric($roleId)) {
				$log->info('Role ID was not a number, maybe it was already removed');
			} else {
				$role = new Role($roleId);
				$role->delete();
			}

			$result = $couch->docRemove($docId);
			$status = true;
		} catch (Phly_Couch_Exception $error) {
			$log->err($error->getMessage());
			$status = true;
		} catch (Exception $error) {
			$log->err($error->getMessage());
			$status = false;
			$message = 'Could not cancel the existing site audit';
		}

		$session->siteAudit = null;
		Zend_Registry::set('nessquik', $session);
		$this->view->response = array(
			'status' => $status,
			'message' => $message
		);
	}
}

?>
