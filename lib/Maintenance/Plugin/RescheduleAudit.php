<?php

/**
* Reschedules any audits which meet the following requirements
*
*	1. date_scheduled is in the past
*	2. status is finished
*	3. scheduling is enabled
*
* For the matching audits, the audits are checked for the following
*
*	1. If the current time is earlier than the time that the
*	   audit is supposed to start scheduling, the audit is skipped
*	   for the current iteration
*	2. If the current time is later than the time that the
*	   audit is supposed to stop scheduling, the audit scheduling
*	   is disabled (set to doesNotRepeat), and the audit is skipped
*	   for the current iteration
*
* If the above two conditions are not met, the next scheduled date is
* calculated for the audit
*
* @author Tim Rupp
*/
class Maintenance_Plugin_RescheduleAudit extends Maintenance_Plugin_Abstract {
	const IDENT = __CLASS__;

	/**
	* @throws Maintenance_Plugin_Exception
	*/
	public function dispatch(Maintenance_Request_Abstract $request) {
		$baseDate = new Zend_Date;
		$log = App_Log::getInstance(self::IDENT);
		$date = new Zend_Date;

		try {
			$audits = $this->_fetchAuditsToReschedule();
			foreach($audits as $audit) {
				$audit = new Audit($audit['id']);

				$log->debug(sprintf('Considering audit with ID "%s" for rescheduling', $audit->id));

				$schedule = $audit->getSchedule();

				if ($schedule['enableScheduling'] == 'doesNotRepeat') {
					$log->info('The audit is configured to not repeat');
					continue;
				}

				$rangeStart = $this->_getRangeStart($schedule);
				$rangeEnd = $this->_getRangeEnd($schedule);

				if ($baseDate->isEarlier($rangeStart)) {
					$log->debug('Base date to schedule from is earlier than the specified range-start date.');
					$log->debug('Skipping this audit. Will check again during next maintenance window');
					return false;
				} else if ($baseDate->isLater($rangeEnd)) {
					$log->debug('Base date is later than specified range-end date.');
					$log->debug('Disabling future scheduling due to range-end having been met');

					$audit->status = 'N';
					$audit->update();
					
					return false;
				}

				$nextSchedule = $audit->schedule->enumerateFutureSchedule(1);
				if (empty($nextSchedule)) {
					continue;
				} else {
					$nextSchedule = new Zend_Date($nextSchedule[0], Zend_Date::W3C);
					$audit->date_scheduled = $nextSchedule;
					$audit->status = 'P';
				}

				$audit->update();
				$log->debug(sprintf('Audit with ID "%s" rescheduled on "%s". It will run on "%s"',
					$audit->id, $date->get(Zend_Date::W3C), $nextSchedule->get(Zend_Date::W3C))
				);
			}

			return true;
		} catch (Exception $error) {
			throw new Maintenance_Plugin_Exception($error->getMessage());
		}
	}

	protected function _getRangeStart($schedule) {
		$rangeStart = new Zend_Date;
		$start = $rangeStart->setDate($schedule['rangeStart'], 'MM/dd/YYYY');
		$start = $start->setTime($schedule['startOnTime'], 'HH:mma');

		return $start;
	}

	protected function _getRangeEnd($schedule) {
		$rangeEnd = new Zend_Date;
		if ($schedule['rangeEnd'] == 'never') {
			$end = $rangeEnd->setYear('3000');
		} else {
			$end = $rangeEnd->setDate($schedule['rangeEnd'], 'MM/dd/YYYY');
		}

		$end = $end->setTime($schedule['startOnTime'], 'HH:mma');

		return $end;
	}

	protected function _fetchAuditsToReschedule() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);
		$date = new Zend_Date;

		$sql = $db->select()
			->from('audits', array('id'))
			->where('status = ?', 'F')
			->where('scheduling = ?', true)
			->where('date_scheduled <= ? OR date_scheduled IS NULL', $date->get(Zend_Date::W3C));

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			return $stmt->fetchAll();
		} catch (Exception $error) {
			throw new Maintenance_Plugin_Exception($error->getMessage());
		}
	}
}

?>
