<?php

/**
* @author Tim Rupp
*/
class SiteAudit_FinalizeController extends Zend_Controller_Action {
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
		$account = new Account($session->siteAudit['accountId']);

		$this->view->assign(array(
			'account' => $account
		));
	}

	public function saveAction() {
		$status = false;
		$message = null;

		$log = App_Log::getInstance(self::IDENT);

		$permissions = new Permissions;
		$session = Zend_Registry::get('nessquik');

		try {
			$subnets = $this->_divideProvidedSubnet($session->siteAudit['subnet']);
			$account = new Account($session->siteAudit['accountId']);

			foreach($subnets as $subnet) {
				$included = array();
				$excluded = array();

				$auditId = Audit_Util::create();
				if ($auditId === false) {
					throw new Zend_Controller_Action_Exception('Could not create the new audit');
				}

				$audit = new Audit($auditId);

				$permission = $permissions->get('Audit', $auditId);
				$result = $account->acl->allow($permission[0]['permission_id']);
				$audit->status = 'N';
				$audit->doc->accountId = $account->id;
				$audit->created = new Zend_Date;
				$audit->last_modified = new Zend_Date;
				$audit->policy = $session->siteAudit['policyId'];

				$audit->name = $subnet;
				$audit->scanner = $session->siteAudit['scannerId'];
				$audit->scheduling = false;
				$audit->last_modified = new Zend_Date;

				$audit->setSchedule(array('enableScheduling' => 'doesNotRepeat'));

				$notifications = $session->siteAudit['notifications'];
				$notifications['subject'] = sprintf('Scan of %s', $subnet);
				$audit->setNotification($notifications);

				$included['network'][] = $subnet;

				if(empty($session->siteAudit['excluded'])) {
					$excluded = array();
				} else {
					foreach($session->siteAudit['excluded'] as $exclude) {
						$type = $exclude['type'];
						$target = $exclude['target'];
						$excluded[$type][] = $target;
					}
				}

				$log->debug('Setting include targets');
				$audit->setInclude($included);

				$log->debug('Setting exclude targets');
				$audit->setExclude($excluded);

				$log->debug('Updating audit details');
				$result = $audit->update();
			}

			$log->debug('Site audit details were successfully finalized');
			$status = true;
			$message = null;
		} catch (Exception $error) {
			$status = false;
			$message = $error->getMessage();
			$log->err($message);
		}

		$log->debug('Nullifying the siteAudit session details');
		$session->siteAudit = null;
		Zend_Registry::set('nessquik', $session);

		$this->view->response = array(
			'status' => $status,
			'message' => $message
		);
	}

	protected function _divideProvidedSubnet($subnet) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);

		$cmd = sprintf('%s %s/bin/divide-subnet.py --target="%s" --subnet="%s" --format=json 2>/dev/null',
			$config->python->path, _ABSPATH, $subnet, '24', 'json'
		);
		$log->debug(sprintf('Running cmd %s', $cmd));

		$output = exec($cmd, $output, $returnVar);
		if ($returnVar > 0) {
			throw new Exception('An error ocurred while running the exclude script.');
		}

		$json = json_decode($output);
		if (empty($json)) {
			return array($subnet);
		} else {
			return $json;
		}
	}
}

?>
