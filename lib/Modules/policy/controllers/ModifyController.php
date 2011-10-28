<?php

/**
* @author Tim Rupp
*/
class Policy_ModifyController extends Zend_Controller_Action {
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

		$this->view->assign(array(
			'action' => $request->getActionName(),
			'config' => $config,
			'controller' => $request->getControllerName(),
			'module' => $request->getModuleName(),
			'session' => $this->session
		));
	}

	public function editAction() {
		$log = App_Log::getInstance(self::IDENT);
		$auth = Zend_Auth::getInstance();
		$config = Ini_Config::getInstance();
		$plugins = array();
		$isNew = false;
		$policy = null;
		$filter = new Zend_Filter_Boolean(Zend_Filter_Boolean::ALL);

		$account = $this->_helper->GetRequestedAccount();
		$accountId = $account->id;

		$request = $this->getRequest();
		$request->setParamSources(array('_GET'));

		$policyId = $request->getParam('id');
		if (empty($policyId)) {
			$policyId = '_new';
		}

		if ($policyId == '_new') {
			$isNew = true;
			$totalPlugins = 0;
			$policyId = Policy_Util::create();

			$permission = new Permissions;
			$permission = $permission->get('Policy', $policyId);

			$result = $account->acl->allow($permission[0]['permission_id']);
			$familyList = Plugin_Util::getFamilies();
			foreach($familyList as $family) {
				$letter = strtoupper(substr($family, 0, 1));
				$families[$letter][] = array(
					'family' => $family,
					'state' => ''
				);
			}
		} else {
			if ($this->_helper->CanChangePolicy($accountId, $policyId)) {
				$policy = new Policy($policyId);
				$totalPlugins = $policy->plugins->count();

				$bundle = new Bundle_PolicyPluginFamily();
				$bundle->policy($policyId);
				$familyList = $bundle->get();
				foreach($familyList as $family) {
					$letter = strtoupper(substr($family['family'], 0, 1));
					$families[$letter][] = $family;
				}
			} else {
				$this->_redirector = $this->_helper->getHelper('Redirector');
				$this->_redirector->gotoSimple('permission-denied', 'error', 'default');
			}
		}

		$this->view->assign(array(
			'referer' => @$_SERVER['HTTP_REFERER'],
			'id' => $policyId,
			'policy' => $policy,
			'isNew' => $isNew,
			'families' => $families,
			'filter' => $filter,
			'totalPlugins' => $totalPlugins
		));
	}

	public function saveAction() {
		$included = array();
		$excluded = array();
		$preferences = array();

		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$auth = Zend_Auth::getInstance();

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$params = $request->getParams();

		$account = $this->_helper->GetRequestedAccount();
		$accountId = $account->id;

		$policyId = $request->getParam('policyId');
		if (empty($policyId)) {
			throw new Zend_Controller_Action_Exception('The policy ID provided to the controller was empty');
		}

		if ($this->_helper->CanChangePolicy($accountId, $policyId)) {
			$policy = new Policy($policyId);
		} else {
			$this->_redirector = $this->_helper->getHelper('Redirector');
			$this->_redirector->gotoSimple('permission-denied', 'error', 'default');
		}

		try {
			$policy->name = $params['preference']['policyName'];
			$policy->description = $params['preference']['policyComments'];
			$policy->last_modified = new Zend_Date;

			if (empty($params['plugin_selection'])) {
				throw new Exception('You did not specify any plugins to include in the scan');
			}

			// Removes the existing preferences so that the new preferences can be inserted
			$policy->preferences->clear();

			$preferences = array_merge($params['plugin_preference'], $params['preference']);
			foreach($preferences as $preference => $value) {
				$value = trim($value);

				if ($value == 'on') {
					$value = 'yes';
				} else if ($value == 'off') {
					$value = 'no';
				}

				$policy->preferences->$preference = $value;
			}

			$result = $policy->update();
			$status = true;
			$message = 'Successfully made the appropriate changes to the policy';
		} catch (Exception $error) {
			$log->err($error->getMessage());
			$status = false;
			$message = 'Failed to make the changes to the policy';
		}

		$this->view->response = array(
			'status' => $status,
			'message' => $message
		);
	}
}

?>
