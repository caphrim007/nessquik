<?php

/**
* @author Tim Rupp
*/
class SiteAudit_ModifyController extends Zend_Controller_Action {
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

	public function saveAction() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$included = array();
		$targets = array();

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$params = $request->getParams();
		$type = $request->getParam('type');
		$dateSwitch = $request->getParam('startDateSwitch');
		$lastScanned = new Zend_Date;

		$account = $this->_helper->GetRequestedAccount();
		$accountId = $account->id;

		$auditId = Audit_Util::create();
		if ($auditId === false) {
			throw new Zend_Controller_Action_Exception('Could not create the new audit');
		}

		$audit = new Audit($auditId);
		$permission = new Permissions;

		$permission = $permission->get('Audit', $auditId);
		$result = $account->acl->allow($permission[0]['permission_id']);
		$audit->status = 'N';
		$audit->doc->accountId = $accountId;

		try {
			if ($dateSwitch == 'general') {
				$general = $request->getParam('generalDate');

				switch($general) {
					case 'last60Minutes':
						$lastScanned = $lastScanned->subMinute(60);
						break;
					case 'last4Hours':
						$lastScanned = $lastScanned->subHour(4);
						break;
					case 'last24Hours':
						$lastScanned = $lastScanned->subHour(24);
						break;
					case 'last7Days':
						$lastScanned = $lastScanned->subDay(7);
						break;
					case 'last30Days':
						$lastScanned = $lastScanned->subDay(30);
						break;
				}
			} else {
				$scanned = $request->getParam('lastScanned');
				$lastScanned = $lastScanned->setDate($scanned, 'yyyy-MM-ddTHH:mm:ss');
				$lastScanned = $lastScanned->setTime($scanned, 'yyyy-MM-ddTHH:mm:ss');
			}

			switch($type) {
				case 'network':
					$network = $request->getParam('network');
					$netmask = $request->getParam('netmask');

					if (!Ip::isIpAddress($network)) {
						throw new Exception('The supplied network is not a valid IP address');
					} else if (!is_numeric($netmask)) {
						throw new Exception('The supplied netmask must be a number');
					}

					$siteSubnet = $network . '/' . $netmask;
					break;
				case 'range':
					$start = $request->getParam('start');
					$end = $request->getParam('end');

					if (!Ip::isIpAddress($start)) {
						throw new Exception('The supplied start address is not a valid IP address');
					} else if (!Ip::isIpAddress($end)) {
						throw new Exception('The supplied end address is not a valid IP address');
					}

					$siteSubnet = Ip::range2cidr($start, $end);
					break;
			}

			$sql = $db->select()
				->from('last_audit', array('target'))
				->where('target <<= ?', $siteSubnet)
				->where('last_audit <= ?', $lastScanned->get(Zend_Date::W3C));

			$log->debug($sql->__toString());

			$stmt = $sql->query();
			$results = $stmt->fetchAll();

			foreach($results as $result) {
				$targets[] = $result['target'];
			}

			$include = Ip::exclude($siteSubnet, $targets);

			if ($account->policy->hasPolicy($params['policyId'])) {
				$audit->policy = $params['policyId'];
			} else {
				throw new Zend_Controller_Action_Exception('You do not have permission to use this policy');
			}

			$schedule = array('enableScheduling' => 'doesNotRepeat');

			$audit->name = $params['name'];
			$audit->scanner = $params['scanner'];
			$audit->created = new Zend_Date;

			$audit->setSchedule($schedule);

			if (empty($include)) {
				throw new Exception('No targets were found during the specified time period.');
			} else {
				foreach($include as $target) {
					$included['network'][] = $target;
				}
			}

			$audit->setInclude($included);

			$result = $audit->update();

			$status = true;
			$message = 'Successfully made the appropriate changes to the audit';
		} catch (Exception $error) {
			$log->err($error->getMessage());
			$status = false;
			$message = 'Failed to make the changes to the audit';
		}

		$this->view->response = array(
			'status' => $status,
			'message' => $message,
			'auditId' => $auditId
		);
	}
}

?>
