<?php

/**
* @author Tim Rupp
*/
class Maintenance_Plugin_RecordLastAudit extends Maintenance_Plugin_Abstract {
	const IDENT = __CLASS__;

	public function dispatch(Maintenance_Request_Abstract $request) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);
		$couch = new Phly_Couch($config->database->couch->params);

		$log->debug('Notified of dispatch. Performing task');

		$conf = $config->queue->get('last-audit');
		if ($conf->options->forupdate) {
			$conf->options->forupdate = true;
		} else {
			$conf->options->forupdate = false;
		}

		// Create a database queue.
		$queue = new Zend_Queue('Db', $conf->toArray());
		if ($queue->count() == 0) {
			$log->debug('Message queue was empty');
			return;
		} else {
			$log->debug(sprintf('Message queue has %s message(s) in it. Asking for %s', $queue->count(), 50));
			$messages = $queue->receive(50);
		}

		try {
			foreach($messages as $message) {
				$json = json_decode($message->body);

				$docId = str_replace('-', '', $json->auditId);
				$doc = $couch->docOpen($docId);
				if (!($doc instanceof Phly_Couch_Document)) {
					$log->err(sprintf('Document with docId "%s" was not found in the database; removing it', $docId));
					$result = $queue->deleteMessage($message);
					if ($result === false) {
						$log->err(sprintf('Failed to delete the message with docId %s from the queue', $docId));
					}
					continue;
				}

				$audit = new Audit($json->auditId);
				$auditedTargets = $audit->getIpTargetList();

				if (!empty($auditedTargets)) {
					$log->info(sprintf('Marking %s targets as having been audited in last_audit table', count($auditedTargets)));

					foreach($auditedTargets as $target) {
						try {
							$data = array(
								'target' => $target,
								'last_audit' => $doc->lastFinished
							);
							$result = $db->insert('last_audit', $data);
							$log->debug(sprintf('Inserted entry for "%s" into the last_audit table', $target));
						} catch (Exception $error) {
							$log->debug(sprintf('Entry for "%s" already exists in the last_audit table. Updating', $target));
							$data = array(
								'last_audit' => $doc->lastFinished
							);
							$where = $db->quoteInto('target = ?', $target);
							$result = $db->update('last_audit', $data, $where);
						}
					}
				} else {
					$log->info('The enumerated target IP list was empty. Weird');
				}

				$result = $queue->deleteMessage($message);
				if ($result === false) {
					$log->err(sprintf('Failed to delete the message with docId %s from the queue', $docId));
				}
			}
		} catch (Exception $error) {
			$log->err($error->getMessage());
			throw new Maintenance_Plugin_Exception($error->getMessage());
		}
	}
}

?>
