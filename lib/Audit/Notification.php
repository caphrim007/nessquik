<?php

/**
* @author Tim Rupp
*/
class Audit_Notification {
	const IDENT = __CLASS__;

	public function parse($notifications) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);

		$result = array();

		$result['sendToMe'] = $notifications['sendToMe'];
		$result['reportFormat'] = $notifications['reportFormat'];

		if (isset($notifications['compressAttachment'])) {
			$result['compressAttachment'] = $notifications['compressAttachment'];
		} else {
			$result['compressAttachment'] = 'no';
		}

		if (isset($notifications['recipients'])) {
			$recipients = $notifications['recipients'];
		} else {
			$recipients = array();
		}

		if ($notifications['sendToOthers'] == 'yes') {
			$result['sendToOthers'] = 'yes';
			if (empty($recipients)) {
				$result['recipients'] = array();
			} else {
				$result['recipients'] = array();
				foreach($recipients as $recipient) {
					if ($recipient == 'email address') {
						continue;
					} else if (preg_match('/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/' , $recipient)) {
						$result['recipients'][] = $recipient;
					}
				}

				// Check to see if the provided recipient list was
				// invalid, and if it was, set the notifying of others
				// back to no.
				if (empty($result['recipients'])) {
					$result['sendToOthers'] = 'no';
				}
			}
		} else {
			$result['sendToOthers'] = 'no';
			$result['recipients'] = array();
		}

		@$subject = $notifications['subject'];
		if (empty($subject)) {
			$result['subject'] = 'nessquik scan report';
		} else {
			$result['subject'] = $subject;
		}

		return $result;
	}
}

?>
