<?php

/**
* @author Tim Rupp
*/
class Audit {
	const IDENT = __CLASS__;

	protected $_id;
	protected $_data;
	protected $_progress;

	public $schedule;
	public $notification;
	public $report;
	public $target;

	public function __construct($id) {
		$log = App_Log::getInstance(self::IDENT);

		$this->_id = $id;

		try {
			$this->_loadAuditData();
			$this->_loadAuditProgress();
		} catch (Exception $error) {
			$log->err($error->getMessage());
			throw new Audit_Exception($error->getMessage());
		}

		$this->report = new Audit_Report($this->_id);
		$this->schedule = new Audit_Schedule($this->_id);
		$this->notification = new Audit_Notification($this->_id);
		$this->target = new Audit_Target($this->_id);
	}

	public function __get($key) {
		switch($key) {
			case 'id':
				return $this->_id;
			case 'policy':
				$key = 'policy_id';
				break;
			case 'progress':
				return $this->_progress;
			case 'startOnTime':
				$date = new Zend_Date;
				return $date;
			default:
				break;
		}

		if (isset($this->_data[$key])) {
			return $this->_data[$key];
		} else {
			return false;
		}
	}

	public function __set($key, $val) {
		switch($key) {
			case 'created':
			case 'last_modified':
			case 'date_scheduled':
			case 'date_started':
			case 'date_finished':
				if ($val instanceof Zend_Date) {
					break;
				} else if ($val === null) {
					break;
				} else {
					$val = new Zend_Date($val, 'YYYY-MM-dd HH:mm:ss-Z');
				}
				break;
			case 'id':
				return false;
			case 'policy':
			case 'policy_id':
				$key = 'policy_id';
				break;
			case 'scanner':
			case 'scanner_id':
				$key = 'scanner_id';
				break;
		}

		$this->_data[$key] = $val;
	}

	public function update() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);
		$filter = new Zend_Filter_Boolean(Zend_Filter_Boolean::ALL);

		$this->last_modified = new Zend_Date;

		foreach($this->_data as $key => $val) {
			switch($key) {
				case 'created':
				case 'last_modified':
				case 'date_scheduled':
				case 'date_finished':
				case 'date_started':
					if ($val instanceof Zend_Date) {
						$data[$key] = $val->get(Zend_Date::W3C);
					}
					break;
				case 'scheduling':
					if ($filter->filter($val) === true) {
						$data[$key] = 1;
					} else {
						$data[$key] = 0;
					}
					break;
				default:
					$data[$key] = $val;
					break;
			}
		}

		$log->debug('Updating audits table');
		$where = $db->quoteInto('id = ?', $this->_id);
		$result = $db->update('audits', $data, $where);

		$log->debug('Updating audits_progress table');
		$where = $db->quoteInto('audit_id = ?', $this->_id);
		$result = $db->update('audits_progress', $this->_progress, $where);
	}

	/**
	* @throws Audit_Exception
	*/
	public function delete() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		try {
			$log->debug(sprintf('Deleting audit with id "%s"', $this->_id));
			$where = $db->quoteInto('id = ?', $this->_id);
			$result = $db->delete('audits', $where);

			return true;
		} catch (Exception $error) {
			throw new Audit_Exception($error->getMessage());
		}
	}

	public function cancel() {
		$config = Ini_Config::getInstance();
		$scanner = new Audit_Server($config->vscan->default);		
	}

	public function start() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$date = new Zend_Date;

		$policy = new Policy($this->policy_id);

		$this->status = 'R';

		$log->debug('Fetching target list');
		$targetList = $this->getTargetList();

		// Create policies on the server at run time
		// This allows nessquik to not have to (re)create policies
		// on every new server that is added.
		$log->debug('Retrieving preference key/value pairs for saving policy to Nessus');
		$preferences = $policy->getPreferencesKv($scanner);
		$options = array(
			'policy_name' => $preferences['policy_name'],
			'policy_shared' => $preferences['policy_shared']
		);

		$log->debug(json_encode($options));
		foreach($preferences as $key => $val) {
			$log->debug(sprintf('Preference: %s = %s', $key, $val));
		}

		$log->debug('Saving audit policy to audit server');
		$newPolicy = $scanner->adapter->savePolicy(0, $options, $preferences);

		$log->debug('Updating audit policy in database');
		$policy->update();
		$log->debug(sprintf('Audit with ID "%s" started on "%s"', $this->id, $date->get(Zend_Date::W3C)));

		return true;
	}

	public function stop() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$account = $this->getAccount();

		if ($this->status == 'R') {
			if (isset($this->doc->nessusAuditId)) {
				try {
					$log->debug('Requesting to stop audit via server adapter');
					$result = $scanner->adapter->stopScan($this->doc->nessusAuditId);

					$date = new Zend_Date($result['start_time'], Zend_Date::TIMESTAMP);
					$mesg = sprintf('Stopping audit with ID "%s", nessusAuditID "%s" '
						. 'which was started on "%s". Scan current completion was '
						. '"%s" targets out of a total of "%s" targets', 
						$this->id, $this->doc->nessusAuditId, $date->get(Zend_Date::W3C),
						$result['completion_current'], $result['completion_total']);

					# Deletes the policy from the scanner to ensure it is cleaned
					# up because policies are created on the scanner at runtime
					$log->info(sprintf('Removing policy with Nessus ID "%s" from Nessus scanner', $policy->doc->nessusPolicyId));
					$policy = new Policy($this->policy_id);
					$scanner->adapter->deletePolicy($policy->doc->nessusPolicyId);

					$log->info($mesg);
				} catch (Exception_Unauthorized $error) {
					$log->err($error->getMessage());
					$log->debug('Account cookie was invalid. Resetting the cookie value and attempting to restop the audit');
					$account->doc->serverCookie = 'reset';
					$account->update();

					$this->stop();
				} catch (Exception $error) {
					$log->err($error->getMessage());
				}
			} else {
				$log->err('The Nessus audit ID was not found in the document');
			}

			unset($this->doc->nessusAuditId);
		}

		$this->status = 'N';
		$this->update();

		return true;
	}

	protected function _loadAuditData() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('audits')
			->where('id = ?', $this->_id);

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			$result = $stmt->fetchAll();
			if (!empty($result)) {
				foreach($result[0] as $key => $val) {
					$this->$key = $val;
				}
			}
		} catch (Exception $error) {
			throw new Audit_Exception($error->getMessage());
		}
	}

	protected function _loadAuditProgress() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('audits_progress')
			->where('audit_id = ?', $this->_id);

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			$result = $stmt->fetchAll();
			if (!empty($result)) {
				$this->setProgress($result[0]);
			}
		} catch (Exception $error) {
			throw new Audit_Exception($error->getMessage());
		}
	}

	public function setProgress($type, $progress = null) {
		if (is_array($type)) {
			if (isset($type['current'])) {
				$this->_progress['current'] = $type['current'];
			}

			if (isset($type['total'])) {
				$this->_progress['total'] = $type['total'];
			}
		} else {
			if (isset($this->_progress[$type])) {
				if (is_numeric($progress)) {
					$this->_progress[$type] = $progress;
				} else {
					throw new Exception('Supplied progress was not a number');
				}
			}
		}
	}

	public function getProgress() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('audits_progress')
			->where('audit_id = ?');

		try {
			$log->debug($sql->__toString());

			$stmt = $sql->query();
			$result = $stmt->fetchAll();

			if (count($result) > 0) {
				return $result[0];
			} else {
				return array();
			}
		} catch (Exception $error) {
			throw new Audit_Exception($error->getMessage());
		}
	}

	public function getResults($page = 1, $limit = 15) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('audits_reports')
			->where('audit_id = ?', $this->_id);

		if (!empty($limit)) {
			$sql->limitPage($page, $limit);
		}

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			$result = $stmt->fetchAll();
		} catch (Exception $error) {
			throw new Audit_Exception($error->getMessage());
		}
	}

	public function getTargetList() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$result = array();
		$format = 'json';
		$includedList = array();
		$excludedList = array();

		$rand = mt_rand(0,100000);
		$tmp = sprintf('%s/tmp/', _ABSPATH);
		$targetTmp = sprintf('%s/tmp/%s.target', _ABSPATH, $rand);
		$excludeTmp = sprintf('%s/tmp/%s.exclude', _ABSPATH, $rand);

		$include = $this->getTargets('include', 'hostname');

		$includedList = $this->target->enumerateInclude();
		$excludedList = $this->target->enumerateExclude();

		if (!is_writable($tmp)) {
			throw new Exception('The nessquik tmp directory is not writable');
		}

		$includedListWrite = implode("\n", $includedList);
		$excludedListWrite = implode("\n", $excludedList);

		$log->debug(sprintf('Creating temporary file "%s" to store included target list. "%s" targets', $targetTmp, count($includedList)));
		file_put_contents($targetTmp, $includedListWrite);

		$log->debug(sprintf('Creating temporary file "%s" to store excluded target list. "%s" targets', $excludeTmp, count($excludedList)));
		file_put_contents($excludeTmp, $excludedListWrite);

		$cmd = sprintf('%s %s/bin/exclude.py --target-file="%s" --exclude-file="%s" --format=json 2>/dev/null',
			$config->python->path, _ABSPATH, $targetTmp, $excludeTmp, $format
		);
		$log->debug(sprintf('Running cmd %s', $cmd));

		$output = exec($cmd, $output, $returnVar);
		if ($returnVar > 0) {
			throw new Exception('An error ocurred while running the exclude script.');
		}

		$json = json_decode($output);

		unlink($targetTmp);
		unlink($excludeTmp);

		return $json;
	}

	public function getIpTargetList() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$result = array();
		$format = 'json';
		$includedList = array();
		$excludedList = array();

		$rand = mt_rand(0,100000);
		$tmp = sprintf('%s/tmp/', _ABSPATH);
		$targetTmp = sprintf('%s/tmp/%s.target', _ABSPATH, $rand);
		$excludeTmp = sprintf('%s/tmp/%s.exclude', _ABSPATH, $rand);

		$include = $this->getTargets('include', 'hostname');

		$includedList = $this->target->enumerateInclude();
		$excludedList = $this->target->enumerateExclude();

		if (!is_writable($tmp)) {
			throw new Exception('The tmp directory is not writable');
		}

		$includedListWrite = implode("\n", $includedList);
		$excludedListWrite = implode("\n", $excludedList);

		$log->debug(sprintf('Creating temporary file "%s" to store included target list. "%s" targets', $targetTmp, count($includedList)));
		file_put_contents($targetTmp, $includedListWrite);

		$log->debug(sprintf('Creating temporary file "%s" to store excluded target list. "%s" targets', $excludeTmp, count($excludedList)));
		file_put_contents($excludeTmp, $excludedListWrite);

		$cmd = sprintf('%s %s/bin/exclude.py --target-file="%s" --exclude-file="%s" --format=json 2>/dev/null',
			$config->python->path, _ABSPATH, $targetTmp, $excludeTmp, $format
		);
		$log->debug(sprintf('Running cmd %s', $cmd));

		$output = exec($cmd, $output, $returnVar);
		if ($returnVar > 0) {
			throw new Exception('An error ocurred while running the exclude script.');
		}

		$json = json_decode($output);

		unlink($targetTmp);
		unlink($excludeTmp);

		return $json;
	}

	public function hasScheduling() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance();
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('audits')
			->where('id = ?', $this->_id)
			->where('scheduling = ?', true)
			->limit(1);

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();

			if ($stmt->rowCount() > 0) {
				return true;
			} else {
				return false;
			}
		} catch (Exception $error) {
			throw new Audit_Exception($error->getMessage());
		}
	}
}

?>
