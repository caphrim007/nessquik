<?php

/**
* @author Tim Rupp
*/
class Maintenance_Plugin_UpdateAuditProgress extends Maintenance_Plugin_Abstract {
	const IDENT = __CLASS__;

	public function dispatch(Maintenance_Request_Abstract $request) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);

		$log->debug('Notified of dispatch; performing task');

		try {
			$log->debug('Beginning update of progress percentages for running scans');
			$this->_updateProgressPercent();

			$log->debug('Beginning check for finished audits');
			$this->_updateFinishedAudits();
		} catch (Exception $error) {
			$log->err($error->getMessage());
			throw new Maintenance_Plugin_Exception($error->getMessage());
		}
	}

	protected function _updateProgressPercent() {
		$config = Ini_Config::getInstance();
		$couch = new Phly_Couch($config->database->couch->params);
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		try {
			$sql = $db->select()
				->from('audits', array('id'))
				->where('status = ?', 'R');

			$stmt = $sql->query();
			$results = $stmt->fetchAll();

			if (empty($results)) {
				$log->info('No running audits were found to update progress for');
			}

			foreach($results as $result) {
				$audit = new Audit($result['id']);
				$account = new Account($audit->doc->accountId);
				$username = (string)$account->doc->serverUsername;
				$password = (string)$account->doc->serverPassword;

				$log->debug(sprintf('Looking up scanner "%s" configuration', $audit->doc->scannerId));

				$scanner = new Audit_Server($audit->doc->scannerId);
				$scannerId = $scanner->id;

				if (isset($account->doc->serverCookie[$scannerId])) {
					$serverCookie = unserialize($account->doc->serverCookie[$scannerId]);
					$log->debug('Reusing authentication token from saved cookie');
					$scanner->adapter->setCookie($serverCookie);
				} else {
					$log->err(sprintf('No serverCookie information was found for scanner with ID "%s"', $scannerId));
				}

				$scannerLog = App_Log::getInstance(get_class($scanner));
				$scanner->adapter->setLogger($scannerLog);

				$scans = $scanner->adapter->listScans();
				foreach($scans as $scan) {
					if ($scan['readableName'] != $audit->id) {
						continue;
					}

					$progress = array(
						'current' => $scan['completion_current'],
						'total' => $scan['completion_total']
					);

					$log->debug(sprintf('Setting progress for audit with ID "%s". Current: "%s", Total: "%s"',
						$audit->id, $progress['current'], $progress['total']
					));
					$audit->setProgress($progress);

					$log->debug('Saving updated audit details back to the database');
					$audit->update();
				}
			}
		} catch (Exception $error) {
			$log->err($error->getMessage());
		}
	}

	/**
	* Updates finished status for an audit
	*
	* The Nessus API will not disclosure audit reports for users
	* other than the one currently logged in. Obviously this is a
	* problem when only using a single account to drive all the
	* backend stuff in nessquik.
	*
	* So this method will log in to the nessus scanner and update
	* any audits that may have been completed so that they are
	* moved from the running state to finished in the nessquik UI.
	*/
	protected function _updateFinishedAudits() {
		$config = Ini_Config::getInstance();
		$couch = new Phly_Couch($config->database->couch->params);
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		try {
			$sql = $db->select()
				->from('audits', array('id'))
				->where('status = ?', 'R');

			$stmt = $sql->query();
			$results = $stmt->fetchAll();

			if (empty($results)) {
				$log->info('No running audits were found to update progress for');
			}

			foreach($results as $result) {
				$audit = new Audit($result['id']);
				$account = new Account($audit->doc->accountId);

				$username = (string)$account->doc->serverUsername;
				$password = (string)$account->doc->serverPassword;

				$log->debug(sprintf('Looking up scanner "%s" configuration', $audit->doc->scannerId));
				$scanner = new Audit_Server($audit->doc->scannerId);
				$scannerId = $scanner->id;

				if (isset($account->doc->serverCookie[$scannerId])) {
					$serverCookie = unserialize($account->doc->serverCookie[$scannerId]);
					$log->debug('Reusing authentication token from saved cookie');
					$scanner->adapter->setCookie($serverCookie);
				} else {
					$log->err(sprintf('Existing cookie details were not found for scanner with ID "%s"', $scannerId));
					continue;
				}

				$scannerLog = App_Log::getInstance(get_class($scanner));
				$scanner->adapter->setLogger($scannerLog);

				$reports = $scanner->adapter->listReports();

				foreach($reports as $report) {
					if ($report['status'] != 'completed') {
						continue;
					}

					if ($report['readableName'] != $audit->id) {
						continue;
					}

					$date = new Zend_Date;
					$uuid = $report['name'];
					$formatVersion = $config->vscan->misc->report->format->version;
					$xml = $scanner->adapter->downloadReport($uuid, false, $formatVersion, true);
					$data = array(
						'auditId' => $audit->id,
						'reportId' => $uuid,
						'docType' => 'report',
						'version' => $formatVersion
					);
					$doc = new Phly_Couch_Document($data);
					$doc->setInlineAttachment('content', $xml, 'text/xml');
					$result = $couch->docSave($doc);
					if ($result === false) {
						throw new Exception('Failed to save the report document details');
					} else {
						$scanner->adapter->deleteReport($uuid);
					}

					$audit->report->create($result->id, $date->get(Zend_Date::W3C));

					# Deletes the policy from the scanner to ensure it is cleaned
					# up because policies are created on the scanner at runtime
					$policy = new Policy($audit->policy_id);
					$log->info(sprintf('Removing policy with Nessus ID "%s" from Nessus scanner', $policy->doc->nessusPolicyId));
					$scanner->adapter->deletePolicy($policy->doc->nessusPolicyId);

					$progress = array(
						'current' => 0,
						'total' => 0
					);
					$audit->setProgress($progress);
					$audit->status = 'F';
					$audit->date_scheduled = null;
					$audit->date_finished = $date;
					$audit->doc->lastFinished = $date->get(Zend_Date::W3C);
					$audit->update();

					$this->_notifyFinishedAudit($audit->id, $result->id);
					$this->_notifyLastAudit($audit->id, $result->id);
					$this->_notifyDashboardRebuild($account->id);
				}
			}
		} catch (Exception $error) {
			$log->err($error->getMessage());
		}
	}

	protected function _notifyFinishedAudit($auditId, $reportId) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);

		$queue = $config->queue->get('audit-finished');
		if ($queue->options->forupdate) {
			$queue->options->forupdate = true;
		} else {
			$queue->options->forupdate = false;
		}
		$queue = new Zend_Queue('Db', $queue->toArray());
		$message = array(
			'auditId' => $auditId,
			'reportId' => $reportId
		);
		$message = json_encode($message);
		$queue->send($message);
		$log->debug('Sent message to queue');
	}

	protected function _notifyLastAudit($auditId, $reportId) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);

		$queue = $config->queue->get('last-audit');
		if ($queue->options->forupdate) {
			$queue->options->forupdate = true;
		} else {
			$queue->options->forupdate = false;
		}
		$queue = new Zend_Queue('Db', $queue->toArray());
		$message = array(
			'auditId' => $auditId,
			'reportId' => $reportId,
		);
		$message = json_encode($message);
		$queue->send($message);
		$log->debug('Sent message to queue');
	}

	protected function _notifyDashboardRebuild($accountId) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);

		$queue = $config->queue->get('rebuild-upcoming-audits');
		if ($queue->options->forupdate) {
			$queue->options->forupdate = true;
		} else {
			$queue->options->forupdate = false;
		}
		$queue = new Zend_Queue('Db', $queue->toArray());
		$message = array(
			'accountId' => $accountId,
		);
		$message = json_encode($message);
		$queue->send($message);
		$log->debug('Sent message to queue');
	}
}

?>
