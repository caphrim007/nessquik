<?php

/**
* @author Tim Rupp
*/
class SiteAudit_AccountController extends Zend_Controller_Action {
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
		$date = new Zend_Date;
		$config = Ini_Config::getInstance();
		$couch = new Phly_Couch($config->database->couch->params);
		$filter = new Zend_Filter_Boolean(Zend_Filter_Boolean::ALL);
		$status = true;
		$message = null;

		if ($filter->filter($session->siteAudit['docId']) === true) {
			if (isset($session->siteAudit['accountId'])) {
				$this->view->assign(array(
					'status' => 'exists',
					'docId' => $session->siteAudit['docId']
				));
			}
		} else {
			$data = array(
				'dateCreated' => $date->get(Zend_Date::W3C),
				'docType' => 'siteAudit'
			);
			$doc = new Phly_Couch_Document($data);
			$result = $couch->docSave($doc);

			$session->siteAudit['docId'] = $result->id;

			$this->view->assign(array(
				'status' => true,
				'docId' => $session->siteAudit['docId']
			));
		}

		$types = Authentication_Util::authTypes();
		$this->view->assign(array(
			'types' => $types
		));
	}

	public function saveAction() {
		$session = Zend_Registry::get('nessquik');
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$username = $request->getParam('username');
		$password = $request->getParam('password');

		try {
			if (empty($username)) {
				throw new Exception('The account username cannot be empty');
			} else if (empty($password)) {
				throw new Exception('The account password cannot be empty');
			}

			$permissions = new Permissions;
			$capability = $permissions->get('Capability', 'targets_view_ipextract');
			$targetIPv4 = $permissions->get('NetworkTarget', '0.0.0.0/0');
			$targetIPv6 = $permissions->get('NetworkTarget', '::/0');

			$accountId = Account_Util::create($username);

			$account = new Account($accountId);
			$account->setPassword($password);

			$roleId = Role_Util::create($account->username, 'Site pentest account');
			$account->role->addRole($roleId);
			$account->setPrimaryRole($roleId);

			$role = new Role($roleId);
			$role->addPermission($capability[0]['permission_id']);
			$role->addPermission($targetIPv4[0]['permission_id']);
			$role->addPermission($targetIPv6[0]['permission_id']);

			$this->_initializeFirstBoot($accountId);

			$session->siteAudit['accountId'] = $accountId;
			$session->siteAudit['roleId'] = $roleId;

			$status = true;
			$message = 'Successfully added the account';
		} catch (Exception $error) {
			$log->err($error->getMessage());
			$status = false;
			$message = $error->getMessage();
		}

		$this->view->response = array(
			'status' => $status,
			'message' => $message
		);
	}

	protected function _initializeFirstBoot($accountId) {
		$log = App_Log::getInstance(self::IDENT);
		$preferences = array();
		$account = new Account($accountId);
		$account->setFirstBoot('off');

		$log->debug('Creating new policy for site audit account');

		$policyId = Policy_Util::create();
		if ($policyId === false) {
			throw new Zend_Controller_Action_Exception('Could not create the new audit');
		}

		$policy = new Policy($policyId);
		$permission = new Permissions;

		$log->debug("Adding new policy permissions to the account's role to allow access.");
		$permission = $permission->get('Policy', $policyId);
		$result = $account->acl->allow($permission[0]['permission_id']);

		$policy->name = 'Default policy';
		$policy->created = new Zend_Date;

		$included['all'][] = 'yes';

		// These are the port scanners
		$included['individual'][14274] = 'on';	// SNMP scanner
		$included['individual'][10335] = 'on';	// TCP scanner
		$included['individual'][14272] = 'on';	// netstat portscanner (SSH)
		$included['individual'][34220] = 'on';	// netstat portscanner (WMI)
		$included['individual'][10180] = 'on';	// Ping the remote host

		$log->debug('Setting plugin selection for new policy');

		$policy->setPluginSelection($included);

		$log->debug('Updating policy details in database');
		$result = $policy->update();
	}
}

?>
