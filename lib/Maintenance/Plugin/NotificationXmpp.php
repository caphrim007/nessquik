<?php

/**
* @author Tim Rupp
*/
class Maintenance_Plugin_NotificationXmpp extends Maintenance_Plugin_Abstract {
	const IDENT = __CLASS__;

	public function dispatch(Maintenance_Request_Abstract $request) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$conn = new XMPPHP_XMPP($config->xmpp->default->params->host,
			$config->xmpp->default->params->port,
			$config->xmpp->default->params->username,
			$config->xmpp->default->params->password,
			$config->xmpp->default->params->resource,
			$config->xmpp->default->params->server,
			$printlog = True,
			$loglevel = XMPPHP_Log::LEVEL_VERBOSE
		);
		$couch = new Phly_Couch($config->database->couch->params);

		$log->debug('Notified of dispatch. Performing task');

		$conf = $config->queue->get('xmpp-audit-finished');
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

				$auditId = str_replace('-','',$json->auditId);
				$doc = $couch->docOpen($json->auditId);
				if (!($doc instanceof Phly_Couch_Document)) {
					$log->err(sprintf('Document with _id "%s" was not found in the database; removing it', $json->_id));
					$result = $queue->deleteMessage($message);
					if ($result === false) {
						$log->err(sprintf('Failed to delete the message with _id %s from the queue', $json->_id));
					}
					continue;
				}

				$url = "https://cst-dev-tst.fnal.gov/nessquik/audit/modify/edit?id=7ddae1cb-c0a2-3e05-5ec5-b2996f4315f9#reports";

				$htmlPayload = "<html xmlns='http://jabber.org/protocol/xhtml-im'>"
				. "<body xmlns='http://www.w3.org/1999/xhtml'><br/><br/>Hi, this is"
				. " the nessquik IM notification server.<br/>We're messaging you to"
				. " tell you that your scan finished.<br/>You can view the results "
				. "<a href=''>here</a></body></html>";

				$conn->connect();
				$conn->processUntil('session_start');
				$conn->message('tarupp@jabber.fnal.gov', "hi", null, 'Your scan is finished!', $htmlPayload);
				$conn->disconnect();
			}
		} catch (Exception $error) {
			$log->err($error->getMessage());
			throw new Maintenance_Plugin_Exception($error->getMessage());
		}
	}
}

?>
