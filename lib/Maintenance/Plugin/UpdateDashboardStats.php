<?php

/**
* @author Tim Rupp
*/
class Maintenance_Plugin_UpdateDashboardStats extends Maintenance_Plugin_Abstract {
	const IDENT = __CLASS__;

	public function dispatch(Maintenance_Request_Abstract $request) {
		$config = Ini_Config::getInstance();
		$maint = Ini_MaintenancePlugin::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->miscomp);

		$log->debug('Notified of dispatch; performing task');

		$conf = $config->queue->get('rebuild-upcoming-audits');
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

		$timeframe = $this->_getWeekTimeframe();

		$log->debug(sprintf('Enumerating audits for timeframe from "%s" to "%s"',
			$timeframe['start']->get(Zend_Date::W3C), $timeframe['end']->get(Zend_Date::W3C)
		));

		try {
			foreach($messages as $message) {
				$json = json_decode($message->body);
				$accountId = $json->accountId;
				$accounts[$accountId][] = $message;
			}

			foreach($accounts as $accountId => $messages) {
				$account = new Account($accountId);

				$log->debug(sprintf('Updating upcoming audits for user "%s', $account->username));

				$this->_updateUpcomingAudits($account, $timeframe['start'], $timeframe['end']);
				$account->update();

				foreach($messages as $message) {
					$result = $queue->deleteMessage($message);
					if ($result === false) {
						$json = json_decode($message->body);
						$log->err(sprintf('Failed to delete the message with _id %s from the queue', $json->_id));
					}
				}
			}
		} catch (Exception $error) {
			$log->err($error->getMessage());
			throw new Maintenance_Plugin_Exception($error->getMessage());
		}

		$log->debug('Finished enumerating upcoming audits');
	}

	public function dispatchSingle(Account $account) {
		$log = App_Log::getInstance(self::IDENT);
		$config = Ini_Config::getInstance();
		$db = App_Db::getInstance($config->database->miscomp);

		$log->debug('Notified of dispatch; performing single task');
		$log->debug(sprintf('Updating upcoming audits for user "%s', $account->username));

		$timeframe = $this->_getWeekTimeframe();

		$log->debug(sprintf('Enumerating audits for timeframe from "%s" to "%s"',
			$timeframe['start']->get(Zend_Date::W3C), $timeframe['end']->get(Zend_Date::W3C)
		));

		$this->_updateUpcomingAudits($account, $timeframe['start'], $timeframe['end']);
		$account->update();
	}

	protected function _updateUpcomingAudits(Account $account, Zend_Date $start, Zend_Date $end) {
		$log = App_Log::getInstance(self::IDENT);

		$log->debug(sprintf('Enumerating upcoming audits for the account "%s"', $account->username));
		$log->debug('Calculating upcoming audits');

		$results = Statistics::calculateUpcoming($account, $start, $end);
		if (empty($results)) {
			$log->debug('No upcoming audits were found for this week timeframe');
			$results = array();
		} else {
			$log->debug(sprintf('"%s" audits were found', count($results)));
		}

		$account->doc->upcomingAudits = $results;
	}

	protected function _getWeekTimeframe() {
		$date = new Zend_Date;

		$date->set('00:00:00', Zend_Date::TIMES);

		// Day 0 is Sunday
		$date->set(0, Zend_Date::WEEKDAY_DIGIT);

		$startDate = $date->get(Zend_Date::W3C);
		$start = new Zend_Date($startDate, Zend_Date::W3C);

		// Day 6 is Saturday
		$date->set(6, Zend_Date::WEEKDAY_DIGIT);
		$endDate = $date->get(Zend_Date::W3C);
		$end = new Zend_Date($endDate, Zend_Date::W3C);

		return array(
			'start' => $start,
			'end' => $end
		);
	}

}

?>
