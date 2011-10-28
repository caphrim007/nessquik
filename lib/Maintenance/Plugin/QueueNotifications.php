<?php

/**
* @author Tim Rupp
*/
class Maintenance_Plugin_QueueNotifications extends Maintenance_Plugin_Abstract {
	protected $_queues;

	const IDENT = __CLASS__;

	public function dispatch(Maintenance_Request_Abstract $request) {
		$config = Ini_Config::getInstance();
		$maint = Ini_MaintenancePlugin::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$couch = new Phly_Couch($config->database->couch->params);
		$filter = new Zend_Filter_Boolean(Zend_Filter_Boolean::ALL);

		$log->debug('Notified of dispatch. Performing task');

		$conf = $config->queue->get('audit-finished');
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

				$auditId = str_replace('-', '', $json->auditId);
				$doc = $couch->docOpen($auditId);
				if (! $doc instanceof Phly_Couch_Document) {
					$log->err(sprintf('Document with _id "%s" was not found in the database; removing it', $json->_id));
					$result = $queue->deleteMessage($message);
					if ($result === false) {
						$log->err(sprintf('Failed to delete the message with _id %s from the queue', $json->_id));
					}
					continue;
				}

				$account = new Account($doc->accountId);

				if ($filter->filter($doc->notification['sendToMe'])) {
					$log->info(sprintf('Queueing message for audit with ID "%s" to send to owner', $auditId));
					$this->_queueEmailAuditFinishedMesg($json);
				} else if ($filter->filter($doc->notification['sendToOthers'])) {
					$log->info(sprintf('Queueing message for audit with ID "%s" to send to others', $auditId));
					$this->_queueEmailAuditFinishedMesg($json);
				} else {
					$log->info(sprintf('The audit with ID "%s" was not configured to send any notifications', $auditId));
				}

				$result = $queue->deleteMessage($message);
				if ($result === false) {
					$log->err(sprintf('Failed to delete the message with _id %s from the queue', $json->_id));
				}
			}
		} catch (Exception $error) {
			$log->err($error->getMessage());
			throw new Maintenance_Plugin_Exception($error->getMessage());
		}
	}

	protected function _queueEmailAuditFinishedMesg($data) {
		$config = Ini_Config::getInstance();
		$conf = $config->queue->get('email-audit-finished');
		if ($conf->options->forupdate) {
			$conf->options->forupdate = true;
		} else {
			$conf->options->forupdate = false;
		}

		$queue = new Zend_Queue('Db', $conf->toArray());
		$queue->send(json_encode($data));
	}

	protected function _queueXmppAuditFinishedMesg($data) {
		$config = Ini_Config::getInstance();
		$conf = $config->queue->get('xmpp-audit-finished');
		if ($conf->options->forupdate) {
			$conf->options->forupdate = true;
		} else {
			$conf->options->forupdate = false;
		}

		$queue = new Zend_Queue('Db', $conf->toArray());
		$queue->send(json_encode($data));
	}
}

?>
