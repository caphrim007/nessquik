<?php

/**
* @author Tim Rupp
*/
class Api_Audit {
	const IDENT = __CLASS__;

	/**
	* @param string $token Access token
	* @param string $auditId ID of the audit to add a target to
	* @param string $target Target to include
	* @param string $type Type of the target. If left empty, nessquik will attempt to guess the type
	* @return boolean
	*/
	public function includeTarget($token, $auditId, $target, $type = null) {
		$allowed = false;

		$log = App_Log::getInstance(self::IDENT);

		try {
			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			if ($account->acl->isAllowed('Audit', $auditId) || $account->acl->isAllowed('Capability', 'edit_audit')) {
				$audit = new Audit($auditId);
			} else {
				throw new Api_Exception(sprintf('Account %s is not allowed to modify this audit', $account->username));
			}

			if (empty($auditId)) {
				throw new Api_Exception('The audit ID provided to the controller was empty');
			}

			if ($type == null) {
				$log->debug('Specified type was null; taking a guess');
				$type = App_Controller_Helper_DetermineTargetType::direct($target);
			}

			if ($type == 'hostname') {
				if (Ip::isIpAddress($target)) {
					$type = 'ipaddress';
					$result = App_Controller_Helper_IncludeTarget::direct($account, $audit, $target, $type);
					if ($result === false) {
						$log->info('IP permission lookup was false, trying hostname');
						// May be allowed to scan the hostname but they specified the IP
						$hostname = gethostbyaddr($target);
						$log->debug(sprintf('Resolved hostname to %s', $hostname));
						// May be an IP
						$result = App_Controller_Helper_IncludeTarget::direct($account, $audit, $hostname, $type);
					}
				} else {
					$result = App_Controller_Helper_IncludeTarget::direct($account, $audit, $target, $type);
					if ($result === false) {
						$log->info('Hostname permission lookup was false, trying IP');
						// May be allowed to scan the IP, but they specified the hostname
						$ip = gethostbyname($target);
						$log->debug(sprintf('Resolved IP to %s', $ip));
						// May be a hostname
						$result = App_Controller_Helper_IncludeTarget::direct($account, $audit, $ip, $type);
					}
				}
			} else {
				$log->debug('Specified type was not a host; trying to include target');
				$result = App_Controller_IncludeTarget::direct($account, $audit, $target, $type);
			}

			$audit->writeDraft();
			return true;
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @param string $token Access token
	* @param string $auditId ID of the audit to append a report message to
	* @param string $message Message to append
	* @return boolean
	*/
	public function appendReport($token, $auditId, $message) {
		$allowed = false;

		$log = App_Log::getInstance(self::IDENT);

		try {
			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			if ($account->acl->isAllowed('Audit', $auditId) || $account->acl->isAllowed('Capability', 'edit_audit')) {
				$audit = new Audit($auditId);
			} else {
				throw new Api_Exception(sprintf('Account %s is not allowed to modify this audit', $account->username));
			}

			if (empty($auditId)) {
				throw new Api_Exception('The audit ID provided to the controller was empty');
			}

			return $audit->report->appendMessage($message);
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @param string $token Access token
	* @param string $auditId ID of the audit to cancel
	* @return boolean
	*/
	public function cancel($token, $auditId) {
		$allowed = false;

		$log = App_Log::getInstance(self::IDENT);

		try {
			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			if ($account->acl->isAllowed('Audit', $auditId) || $account->acl->isAllowed('Capability', 'edit_audit')) {
				$audit = new Audit($auditId);
			} else {
				throw new Api_Exception(sprintf('Account %s is not allowed to modify this audit', $account->username));
			}

			if (empty($auditId)) {
				throw new Api_Exception('The audit ID provided to the controller was empty');
			}

			return $audit->deleteDraft();
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @param string $token Access token
	* @param string $accountId ID of the account to count runnings scans for
	* @return integer
	*/
	public function countRunning($token, $accountId = null) {
		$log = App_Log::getInstance(self::IDENT);

		try {
			$session = Api_Util::getAccount($token);
			if ($session === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($session);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $session->username));
			}

			if ($accountId === null) {
				if (!$session->acl->isAllowed('Capability', 'view_audit_counters')) {
					throw new Api_Exception('Account is not allowed to view all audit counters');
				} else {
					return Audit_Util::count('Running');
				}
			} else if (is_numeric($accountId)) {
				$account = new Account($accountId);

				if (!$session->acl->isAllowed('Capability', 'view_audit_counters') && $session->id != $account->id) {
					throw new Api_Exception('Account is not allowed to view audit counters for specified account');
				} else {
					return $account->audit->count('Running');
				}
			}
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return 0;
		}
	}

	/**
	* @param string $token Access token
	* @return string
	*/
	public function create($token) {
		$log = App_Log::getInstance(self::IDENT);

		try {
			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$auditId = Audit_Util::create();
			if ($auditId === false) {
				throw new Zend_Controller_Action_Exception('Could not create the new audit');
			}

			$audit = new Audit($auditId);
			$audit->setName('New audit');
			$audit->setPolicy($account->policy->getDefaultPolicyId());
			$permission = new Permissions;

			$permission = $permission->get('Audit', $auditId);
			$result = $account->acl->allow($permission[0]['permission_id']);

			$audit->draft();

			return $auditId;
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return null;
		}
	}

	/**
	* @param string $token Access token
	* @param string $auditId ID of the audit to delete
	* @return boolean
	*/
	public function delete($token, $auditId) {
		$allowed = false;
		$log = App_Log::getInstance(self::IDENT);

		try {
			if (empty($auditId)) {
				throw new Api_Exception('The audit ID provided to the API was empty');
			}

			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			if ($account->audit->hasAudit($auditId) || $account->acl->isAllowed('Capability', 'edit_audit')) {
				$audit = new Audit($auditId);
			} else {
				throw new Api_Exception(sprintf('Account %s is not allowed to delete this audit', $account->username));
			}

			$result = $audit->deleteDraft();
			$result = $audit->delete();
			return $result;
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @param string $token Access token
	* @param string $auditId ID of the audit to delete a report from
	* @param string $reportId ID of the report to delete
	* @return boolean
	*/
	public function deleteReport($token, $auditId, $reportId) {
		$allowed = false;
		$log = App_Log::getInstance(self::IDENT);

		try {
			if (empty($auditId)) {
				throw new Api_Exception('The audit ID provided to the API was empty');
			}

			if (empty($reportId)) {
				throw new Api_Exception('The report ID provided to the API was empty');
			}

			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			if ($account->audit->hasAudit($auditId) || $account->acl->isAllowed('Capability', 'edit_audit')) {
				$audit = new Audit($auditId);
			} else {
				throw new Api_Exception(sprintf('Account %s is not allowed to delete this audit', $account->username));
			}

			$report = $audit->report->get($reportId);
			if ($report === false) {
				throw new Zend_Controller_Action_Exception('The specified report was not found in this audit');
			} else {
				return $report->delete();
			}
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @param string $token Access token
	* @param string $auditId ID of the audit to exclude targets from
	* @param audit|string|struct $target Target to exclude
	* @return boolean
	*/
	public function excludeTarget($token, $auditId, $target) {
		$allowed = false;

		$log = App_Log::getInstance(self::IDENT);

		try {
			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			if ($account->acl->isAllowed('Audit', $auditId) || $account->acl->isAllowed('Capability', 'edit_audit')) {
				$audit = new Audit($auditId);
			} else {
				throw new Api_Exception(sprintf('Account %s is not allowed to modify this audit', $account->username));
			}

			if (empty($auditId)) {
				throw new Api_Exception('The audit ID provided to the controller was empty');
			}

			if ($type == null) {
				$log->debug('Specified type was null; taking a guess');
				$type = App_Controller_Helper_DetermineTargetType::direct($target);
			}

			if ($type == 'hostname') {
				if (Ip::isIpAddress($target)) {
					$type = 'ipaddress';
					$result = App_Controller_Helper_ExcludeTarget::direct($account, $audit, $target, $type);
					if ($result === false) {
						$log->info('IP permission lookup was false, trying hostname');
						// May be allowed to scan the hostname but they specified the IP
						$hostname = gethostbyaddr($target);
						$log->debug(sprintf('Resolved hostname to %s', $hostname));
						// May be an IP
						$result = App_Controller_Helper_ExcludeTarget::direct($account, $audit, $hostname, $type);
					}
				} else {
					$result = App_Controller_Helper_ExcludeTarget::direct($account, $audit, $target, $type);
					if ($result === false) {
						$log->info('Hostname permission lookup was false, trying IP');
						// May be allowed to scan the IP, but they specified the hostname
						$ip = gethostbyname($target);
						$log->debug(sprintf('Resolved IP to %s', $ip));
						// May be a hostname
						$result = App_Controller_Helper_ExcludeTarget::direct($account, $audit, $ip, $type);
					}
				}
			} else {
				$log->debug('Specified type was not a host; trying to exclude target');
				$result = App_Controller_ExcludeTarget::direct($account, $audit, $target, $type);
			}

			$audit->writeDraft();
			return true;
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @param string $token Access token
	* @param string $status Status of audits to get
	*/
	public function get($token, $status) {

	}

	/**
	* @param string $token Access token
	* @param string $date Date to restrict pending audits to
	* @param integer|string $page Page of results to return
	* @param integer|string $limit Number of results to return per page
	* @return array|struct
	*/
	public function getPending($token, $date = null, $page = 1, $limit = 15) {
		$allowed = false;
		$results = array();
		$log = App_Log::getInstance(self::IDENT);

		try {
			if ($date === null) {
				$date = new Zend_Date;
			} else if (is_numeric($date)) {
				$date = new Zend_Date($date, Zend_Date::TIMESTAMP);
			} else {
				$date = new Zend_Date($date, Zend_Date::ISO_8601);
			}

			$session = Api_Util::getAccount($token);
			if ($session === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($session);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $session->username));
			}

			if (!$session->acl->isAllowed('Capability', 'edit_audit')) {
				throw new Api_Exception('Account is not allowed to select from all pending audits');
			} else {
				$log->debug('Querying for all pending audits');
				$audits = Audit_Util::get('Pending', $page, $limit);

				foreach($audits as $audit) {
					$schedule = new Zend_Date($audit['date_scheduled']);
					if($date->isLater($schedule)) {
						$results[] = $audit;
					}
				}

				return $results;
			}
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return array();
		}
	}

	/**
	* @param string $token Access token
	* @param string $auditId ID of the audit to get the progress of
	* @return array|struct
	*/
	public function getProgress($token, $auditId) {
		$allowed = false;

		$log = App_Log::getInstance(self::IDENT);

		try {
			if (empty($auditId)) {
				throw new Api_Exception('The audit ID provided to the controller was empty');
			}

			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			if ($account->acl->isAllowed('Audit', $auditId) || $account->acl->isAllowed('Capability', 'edit_audit')) {
				$audit = new Audit($auditId);
			} else {
				throw new Api_Exception(sprintf('Account %s is not allowed to modify this audit', $account->username));
			}

			return $audit->getProgress();
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return array();
		}
	}

	/**
	* @param string $token Access token
	* @param string $auditId ID of the audit to get the report from
	* @param string $reportId ID of the report to return
	* @param string $type Format to return the report in
	* @return string
	*/
	public function getReport($token, $auditId, $reportId, $type = 'nessus') {
		$log = App_Log::getInstance(self::IDENT);

		try {
			return Audit::getReport($audit_id, $type, $report_id);
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return null;
		}
	}

	/**
	* @param string $token Access token
	* @param string $auditId ID of the audit to get the schedule for
	* @return array|struct
	*/
	public function getSchedule($token, $auditId) {
		$allowed = false;
		$log = App_Log::getInstance(self::IDENT);

		try {
			if (empty($auditId)) {
				throw new Api_Exception('The audit ID provided to the controller was empty');
			}

			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			if ($account->acl->isAllowed('Audit', $auditId) || $account->acl->isAllowed('Capability', 'edit_audit')) {
				$audit = new Audit($auditId);
			} else {
				throw new Api_Exception(sprintf('Account %s is not allowed to read this audit', $account->username));
			}

			return $audit->getSchedule();
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return array();
		}
	}

	/**
	* @param string $token Access token
	* @param string $auditId ID of the audit to get the status of
	* @return string
	*/
	public function getStatus($token, $auditId) {
		$allowed = false;

		$log = App_Log::getInstance(self::IDENT);

		try {
			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			if ($account->acl->isAllowed('Audit', $auditId) || $account->acl->isAllowed('Capability', 'edit_audit')) {
				$audit = new Audit($auditId);
			} else {
				throw new Api_Exception(sprintf('Account %s is not allowed to read this audit', $account->username));
			}

			if (empty($auditId)) {
				throw new Api_Exception('The audit ID provided to the controller was empty');
			}

			return $audit->getStatus();
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return null;
		}
	}

	/**
	* @param string $token Access token
	* @param string $auditId ID of the audit to get the targets of
	* @param string $type Type of targets to return
	* @param string $status Status of targets to return
	* @return array|struct
	*/
	public function getTargets($token, $auditId, $type = null, $status = null) {
		$results = array();
		$allowed = false;
		$allStatus = array('enabled', 'disabled');
		$allTypes = array('hostname','network','range','cluster','vhost');
		$log = App_Log::getInstance(self::IDENT);

		try {
			if (empty($auditId)) {
				throw new Api_Exception('The audit ID provided to the controller was empty');
			}

			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			if ($account->acl->isAllowed('Audit', $auditId) || $account->acl->isAllowed('Capability', 'edit_audit')) {
				$audit = new Audit($auditId);
			} else {
				throw new Api_Exception(sprintf('Account %s is not allowed to read this audit', $account->username));
			}

			$log->debug(sprintf('Specifed type %s', $type));

			switch($type) {
				case 'hostname':
				case 'network':
				case 'range':
				case 'cluster':
				case 'vhost':
					return $audit->targets[$type]->getTargets($status);
					break;
				case null:
					foreach($allTypes as $type) {
						foreach($allStatus as $status) {
							$results = array_merge($results, $audit->targets[$type]->getTargets($status));
						}
					}
					return $results;
				default:
					throw new Api_Exception('Invalid type specified');
			}
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return array();
		}
	}

	/**
	* @param string $token Access token
	* @param string $auditId ID of the audit to check for cancellation
	* @return boolean
	*/
	public function isCanceled($token, $auditId) {
		$allowed = false;

		$log = App_Log::getInstance(self::IDENT);

		try {
			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			if ($account->acl->isAllowed('Audit', $auditId) || $account->acl->isAllowed('Capability', 'edit_audit')) {
				$audit = new Audit($auditId);
			} else {
				throw new Api_Exception(sprintf('Account %s is not allowed to read this audit', $account->username));
			}

			if (empty($auditId)) {
				throw new Api_Exception('The audit ID provided to the controller was empty');
			}

			return $audit->isCanceled();
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @param string $token Access token
	* @param string $auditId ID of the audit to remove a target from
	* @param array|string|struct $target Target to be removed
	* @param string $type The type of target to remove.
	* @return boolean
	*/
	public function removeTarget($token, $auditId, $target, $type = 'hostname') {
		$allowed = false;

		$log = App_Log::getInstance(self::IDENT);

		try {
			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			if ($account->acl->isAllowed('Audit', $auditId) || $account->acl->isAllowed('Capability', 'edit_audit')) {
				$audit = new Audit($auditId);
			} else {
				throw new Api_Exception(sprintf('Account %s is not allowed to modify this audit', $account->username));
			}

			if (empty($auditId)) {
				throw new Api_Exception('The audit ID provided to the controller was empty');
			}

			return $audit->removeTarget($target, $type);
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @param string $token Access token
	* @param string $auditId ID of the audit to save
	* @return boolean
	*/
	public function save($token, $auditId) {
		$allowed = false;
		$log = App_Log::getInstance(self::IDENT);

		try {
			if (empty($auditId)) {
				throw new Api_Exception('The audit ID provided to the controller was empty');
			}

			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			if ($account->acl->isAllowed('Audit', $auditId) || $account->acl->isAllowed('Capability', 'edit_audit')) {
				$audit = new Audit($auditId);
			} else {
				throw new Api_Exception(sprintf('Account %s is not allowed to modify this audit', $account->username));
			}

			$audit->write();
			$audit->deleteDraft();
			return true;
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @param string $token Access token
	* @param string $auditId ID of the audit to save a report for
	* @return boolean
	*/
	public function saveReport($token, $auditId) {
		$allowed = false;
		$log = App_Log::getInstance(self::IDENT);

		try {
			if (empty($auditId)) {
				throw new Api_Exception('The audit ID provided to the controller was empty');
			}

			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			if ($account->acl->isAllowed('Audit', $auditId) || $account->acl->isAllowed('Capability', 'edit_audit')) {
				$audit = new Audit($auditId);
			} else {
				throw new Api_Exception(sprintf('Account %s is not allowed to modify this audit', $account->username));
			}

			$audit = new Audit($auditId);
			$report = Audit_Report_Util::composeReportFromMessages($auditId);

			$startTime = $report->getStartTime();
			$date = new Zend_Date($startTime, Zend_Date::ISO_8601);
			$report->setName($date->get(Zend_Date::RSS));
			$report->write();
			return $audit->report->clearMessages();
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @param string $token Access token
	* @param string $auditId ID of the audit to select the report from
	* @param string $reportId ID of the report to email
	* @return boolean
	*/
	public function sendReport($token, $auditId, $reportId) {
		$allowed = false;
		$log = App_Log::getInstance(self::IDENT);

		try {
			if (empty($auditId)) {
				throw new Api_Exception('The audit ID provided to the controller was empty');
			}

			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			if ($account->acl->isAllowed('Audit', $auditId) || $account->acl->isAllowed('Capability', 'edit_audit')) {
				$audit = new Audit($auditId);
			} else {
				throw new Api_Exception(sprintf('Account %s is not allowed to modify this audit', $account->username));
			}

			$notification = $audit->notification->getNotification();
			return $audit->report->sendReport($reportId, $notification);
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @param string $token Access token
	* @param string $auditId ID of the audit to set progress on
	* @param string|integer $progress The progress to set on the audit
	* @param string $type The type to set the progress for
	* @return boolean
	*/
	public function setProgress($token, $auditId, $progress, $type = 'audit') {
		$allowed = false;
		$log = App_Log::getInstance(self::IDENT);

		try {
			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			if ($account->acl->isAllowed('Audit', $auditId) || $account->acl->isAllowed('Capability', 'edit_audit')) {
				$audit = new Audit($auditId);
			} else {
				throw new Api_Exception(sprintf('Account %s is not allowed to modify this audit', $account->username));
			}

			if (empty($auditId)) {
				throw new Api_Exception('The audit ID provided to the controller was empty');
			}

			if ($audit->getStatus() == 'R') {
				return $audit->setProgress($type, $progress);
			} else {
				throw new Api_Exception('Progress can only be set while a scan is running');
			}
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @param string $token Access token
	* @param string $auditId The ID of the audit to set a schedule for
	* @param array|struct $schedule The schedule to run the audit by
	* @return boolean
	*/
	public function setSchedule($token, $auditId, $schedule) {
		$allowed = false;
		$log = App_Log::getInstance(self::IDENT);

		try {
			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			if ($account->acl->isAllowed('Audit', $auditId) || $account->acl->isAllowed('Capability', 'edit_audit')) {
				$audit = new Audit($auditId);
			} else {
				throw new Api_Exception(sprintf('Account %s is not allowed to modify this audit', $account->username));
			}

			if (empty($auditId)) {
				throw new Api_Exception('The audit ID provided to the controller was empty');
			}

			return $audit->setSchedule($schedule);
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @param string $token Access token
	* @param string $auditId ID of the audit to set a status on
	* @param integer|string $status The status to set the audit to
	* @return boolean
	*/
	public function setStatus($token, $auditId, $status) {
		$allowed = false;

		$log = App_Log::getInstance(self::IDENT);

		try {
			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			if ($account->acl->isAllowed('Audit', $auditId) || $account->acl->isAllowed('Capability', 'edit_audit')) {
				$audit = new Audit($auditId);
			} else {
				throw new Api_Exception(sprintf('Account %s is not allowed to modify this audit', $account->username));
			}

			if (empty($auditId)) {
				throw new Api_Exception('The audit ID provided to the controller was empty');
			}

			return $audit->setStatus($status);
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @param string $token Access token
	* @param string $auditId ID of the audit to set a policy on
	* @param string $policyId ID of the policy to set on the audit
	* @return boolean
	*/
	public function setPolicy($token, $auditId, $policyId) {
		$allowed = false;

		$log = App_Log::getInstance(self::IDENT);

		try {
			if (empty($auditId)) {
				throw new Api_Exception('The audit ID provided to the controller was empty');
			}

			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			if ($account->acl->isAllowed('Audit', $auditId)) {
				$audit = new Audit($auditId);
				if ($account->policy->hasPolicy($policyId)) {
					return $audit->setPolicy($policyId);
				} else {
					throw new Zend_Controller_Action_Exception('You do not have permission to use this policy');
				}
			} else if ($account->acl->isAllowed('Capability', 'edit_audit')) {
				$audit = new Audit($auditId);
				return $audit->setPolicy($policyId);
			} else {
				throw new Api_Exception(sprintf('Account %s is not allowed to modify this audit', $account->username));
			}
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @param string $token Access token
	* @param string $auditId ID of the audit to start
	* @return boolean
	*/
	public function start($token, $auditId) {
		$allowed = false;
		$log = App_Log::getInstance(self::IDENT);

		try {
			if (empty($auditId)) {
				throw new Api_Exception('The audit ID provided to the controller was empty');
			}

			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			if ($account->acl->isAllowed('Audit', $auditId) || $account->acl->isAllowed('Capability', 'edit_audit')) {
				$audit = new Audit($auditId);
			} else {
				throw new Api_Exception(sprintf('Account %s is not allowed to modify this audit', $account->username));
			}

			return $audit->start();
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @param string $token Access token
	* @param string $auditId ID of the audit to get the file contents of
	* @param string $format Format of the audit file that you want returned.
	* @param string $raw Specify whether you want the raw audit file as stored
	*	by nessquik, or the audit file with exclusions, clusters and other
	*	things evaluated.
	* @return string
	*/
	public function getAuditFile($token, $auditId, $format = 'nessus', $raw = false) {
		$allowed = false;
		$log = App_Log::getInstance(self::IDENT);

		try {
			if (empty($auditId)) {
				throw new Api_Exception('The audit ID provided to the controller was empty');
			}

			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			if ($account->acl->isAllowed('Audit', $auditId) || $account->acl->isAllowed('Capability', 'edit_audit')) {
				$audit = new Audit($auditId);
			} else {
				throw new Api_Exception(sprintf('Account %s is not allowed to view this audit', $account->username));
			}

			return $audit->get($format, $raw);
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return null;
		}
	}

	/**
	* @param string $token Access token
	* @param string $auditId ID of the audit to get the file contents of
	* @return array|struct
	*/
	public function getParams($token, $auditId) {
		$allowed = false;
		$log = App_Log::getInstance(self::IDENT);

		try {
			if (empty($auditId)) {
				throw new Api_Exception('The audit ID provided to the controller was empty');
			}

			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			if ($account->acl->isAllowed('Audit', $auditId) || $account->acl->isAllowed('Capability', 'edit_audit')) {
				$audit = new Audit($auditId);
			} else {
				throw new Api_Exception(sprintf('Account %s is not allowed to view this audit', $account->username));
			}

			return $audit->getParams();
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return null;
		}
	}

	/**
	* @param string $token Access token
	* @param string $auditId ID of the audit to clear the report messages of
	* @return boolean
	*/
	public function clearMessages($token, $auditId) {
		$allowed = false;

		$log = App_Log::getInstance(self::IDENT);

		try {
			if (empty($auditId)) {
				throw new Api_Exception('The audit ID provided to the controller was empty');
			}

			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			if ($account->acl->isAllowed('Audit', $auditId) || $account->acl->isAllowed('Capability', 'edit_audit')) {
				$audit = new Audit($auditId);
			} else {
				throw new Api_Exception(sprintf('Account %s is not allowed to modify this audit', $account->username));
			}

			return $audit->report->clearMessages();
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}
}

?>
