<?php

/**
* @author Tim Rupp
*/
class Policy_FamilyController extends Zend_Controller_Action {
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

		$this->view->assign(array(
			'action' => $request->getActionName(),
			'config' => $config,
			'controller' => $request->getControllerName(),
			'module' => $request->getModuleName(),
			'session' => $this->session
		));
	}

	public function listAction() {
		$results = array();

		$log = App_Log::getInstance(self::IDENT);
		$request = $this->getRequest();
		$request->setParamSources(array('_GET'));

		$query = $request->getParam('query');
		$family = $request->getParam('family');
		$filter = $request->getParam('filter');
		$filterType = $request->getParam('filterType');
		$policyId = $request->getParam('policyId');

		$account = $this->_helper->GetRequestedAccount();
		$accountId = $account->id;

		try {
			if (!$this->_helper->CanChangePolicy($accountId, $policyId)) {
				throw new Exception('You do not have permission to modify this policy');
			}

			if (empty($page)) {
				$page = 1;
			}

			$bundle = new Bundle_PolicyPluginFamily();
			$bundle->policy($policyId);

			if (!empty($filter)) {
				$bundle->filter($filter, $filterType);
			}

			$familyList = $bundle->get();
			foreach($familyList as $family) {
				$letter = strtoupper(substr($family['family'], 0, 1));
				$families[$letter][] = $family;
			}

			$this->view->assign(array(
				'results' => $families,
			));
			$message = $this->view->render('family/list-results.phtml');

			$status = true;
		} catch (Exception $error) {
			$status = false;
			$message = $error->getMessage();
			$log->err($message);
		}

		$response['status'] = $status;
		$response['message'] = $message;

		$this->view->response = $response;
	}

	public function stateAction() {
		$status = false;
		$message = null;

		$log = App_Log::getInstance(self::IDENT);
		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$policyId = $request->getParam('policyId');
		$family = $request->getParam('family');
		$state = $request->getParam('state');

		$account = $this->_helper->GetRequestedAccount();
		$accountId = $account->id;

		try {
			if ($this->_helper->CanChangePolicy($accountId, $policyId)) {
				$policy = new Policy($policyId);

				switch($state) {
					case 'disable':
						$policy->plugins->disableFamily($family);
						$status = true;
						break;
					case 'enable':
						$policy->plugins->enableFamily($family);
						$status = true;
						break;
					default:
						throw new Exception('The specified state is unknown');
				}
			} else {
				throw new Exception('You are not allowed to modify the specified policy');
			}
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
