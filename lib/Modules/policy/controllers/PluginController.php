<?php

/**
* @author Tim Rupp
*/
class Policy_PluginController extends Zend_Controller_Action {
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
		$limit = 10;

		$log = App_Log::getInstance(self::IDENT);
		$request = $this->getRequest();
		$request->setParamSources(array('_GET'));

		$query = $request->getParam('query');
		$family = $request->getParam('family');
		$filter = $request->getParam('filter');
		$filterType = $request->getParam('filterType');
		$page = $request->getParam('page');
		$policyId = $request->getParam('policyId');

		try {
			if (empty($page)) {
				$page = 1;
			}

			$bundle = new Bundle_PolicyPluginIndividual();
			$bundle->policy($policyId);
			$bundle->page($page);
			$bundle->limit($limit);

			if (!empty($filter)) {
				$bundle->filter($filter, $filterType);
			}

			if (!empty($family)) {
				$bundle->family($family);
			}

			$results = $bundle->get();
			$totalPlugins = $bundle->count(false);
			$totalPages = ceil($totalPlugins / $limit);

			$this->view->assign(array(
				'limit' => $limit,
				'page' => $page,
				'results' => $results
			));
			$message = $this->view->render('plugin/list-results.phtml');
			$this->view->clearVars();

			$response['totalPlugins'] = $totalPlugins;
			$response['totalPages'] = $totalPages;
			$response['currentPage'] = $page;

			$status = true;
		} catch (Exception $error) {
			$status = false;
			$message = $error->getMessage();
			$log->err($message);
		}

		$response['family'] = $family;
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
		$pluginId = $request->getParam('pluginId');
		$state = $request->getParam('state');

		$account = $this->_helper->GetRequestedAccount();
		$accountId = $account->id;

		try {
			if ($this->_helper->CanChangePolicy($accountId, $policyId)) {
				$policy = new Policy($policyId);

				switch($state) {
					case 'disable':
						$policy->plugins->disablePlugin($pluginId);
						$status = true;
						break;
					case 'enable':
						$policy->plugins->enablePlugin($pluginId);
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
