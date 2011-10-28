<?php

/**
* @author Tim Rupp
*/
class Maintenance_Plugin_NotificationEmail extends Maintenance_Plugin_Abstract {
	const IDENT = __CLASS__;

	public function dispatch(Maintenance_Request_Abstract $request) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$couch = new Phly_Couch($config->database->couch->params);
		$bogus = new App_Controller_Helper_FilterBogusAddress;
		$filter = new Zend_Filter_Boolean(Zend_Filter_Boolean::ALL);

		$log->debug('Notified of dispatch. Performing task');

		$conf = $config->queue->get('email-audit-finished');
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
				if (!($doc instanceof Phly_Couch_Document)) {
					$log->err(sprintf('Document with _id "%s" was not found in the database; removing it', $json->_id));
					$result = $queue->deleteMessage($message);
					if ($result === false) {
						$log->err(sprintf('Failed to delete the message with _id %s from the queue', $json->_id));
					}
					continue;
				}

				$account = new Account($doc->accountId);
				$audit = new Audit($json->auditId);
				$name = $account->proper_name;

				$emails = $account->doc->emailContact;

				if (empty($emails)) {
					$emails = array($account->username . '@localhost.localdomain');
					$log->err(sprintf('The email contact list for account with ID "%s" was empty.', $account->id));
					$log->err('Continuing as the owner may have asked to send to other people');
				} else {
					if (!is_array($emails)) {
						$emails = array($emails);
					}

					$emails = $bogus->direct($emails);
					if ($filter->filter($audit->doc->notification['sendToMe'])) {
						$log->debug('Asked to send copy of audit report to the owner');
						foreach($emails as $email) {
							$params = $audit->doc->notification;
							$params['sendToOthers'] = 'no';
							$params['sendFromName'] = $name;
							$params['sendFromAddr'] = $email;

							$status = $audit->report->sendReport($json->reportId, $params);
						}
					}
				}

				if ($filter->filter($audit->doc->notification['sendToOthers'])) {
					$params = $audit->doc->notification;
					$params['sendToMe'] = 'no';
					$params['sendFromAddr'] = $emails[0];
					$params['sendFromName'] = $name;
					$params['recipients'] = $bogus->direct($params['recipients']);

					if (empty($params['recipients'])) {
						$log->err(sprintf('The recipient list for account with ID "%s" was empty. Deleting message from queue', $account->id));
						$result = $queue->deleteMessage($message);
						if ($result === false) {
							$log->err(sprintf('Failed to delete the message with _id %s from the queue', $json->_id));
						}
					}

					$status = $audit->report->sendReport($json->reportId, $params);
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
}

?>
