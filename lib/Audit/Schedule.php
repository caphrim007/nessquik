<?php

/**
* @author Tim Rupp
*/
class Audit_Schedule {
	protected $_id;

	const IDENT = __CLASS__;

	public function __construct($auditId) {
		$this->_id = $auditId;
	}

	public function parse($schedule) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);

		$result = array();

		if (!isset($schedule['enableScheduling'])) {
			return array();
		}

		switch($schedule['enableScheduling']) {
			case 'daily':
				$result['enableScheduling'] = 'daily';
				$result['repeatEvery'] = $schedule['repeatEvery'];
				break;
			case 'everyWeekday':
			case 'everyMonWedFri':
			case 'everyTueThu':
				$result['enableScheduling'] = $schedule['enableScheduling'];
				break;
			case 'weekly':
				$result['enableScheduling'] = 'weekly';
				$result['repeatEvery'] = $schedule['repeatEvery'];

				if (!isset($schedule['repeatOn'])) {
					$schedule['repeatOn'] = '0';
				} else if (is_array($schedule['repeatOn'])) {
					$schedule['repeatOn'] = implode(',',$schedule['repeatOn']);
				}

				$result['repeatOn'] = $schedule['repeatOn'];
				break;
			case 'monthly':
				$result['enableScheduling'] = 'monthly';
				$result['repeatEvery'] = $schedule['repeatEvery'];
				$result['repeatBy'] = $schedule['repeatBy'];
				break;
			case 'yearly':
				$result['enableScheduling'] = 'yearly';
				$result['repeatEvery'] = $schedule['repeatEvery'];
				break;
			case 'doesNotRepeat':
				$result['enableScheduling'] = 'doesNotRepeat';
				break;
			default:
				throw new Audit_Exception('Unknown scheduling type specified');
		}

		if ($schedule['enableScheduling'] != 'doesNotRepeat') {
			$result['startOnTime'] = $schedule['startOnTime'];
			$result['rangeStart'] = $schedule['rangeStart'];
			if ($schedule['rangeEnd'] == 'never') {
				$result['rangeEnd'] = $schedule['rangeEnd'];
			} else {
				$result['rangeEnd'] = $schedule['rangeUntil'];
			}
		}

		return $result;
	}

	public function enumerateFutureSchedule($count = 7) {
		$results = array();
		$schedules = $this->enumerateSchedules();

		$now = new Zend_Date;
		$audit = new Audit($this->_id);

		$schedule = $audit->getSchedule();

		if ($schedule['enableScheduling'] == 'doesNotRepeat') {
			$log->info('The audit is configured to not repeat');
			return array();
		}

		$rangeEnd = $this->_getRangeEnd($schedule);

		if (empty($schedules)) {
			return array();
		}

		foreach($schedules as $schedule) {
			$current = new Zend_Date($schedule, Zend_Date::W3C);
			if ($current->isLater($now) && $current->isEarlier($rangeEnd)) {
				$results[] = $schedule;
			}
		}

		if ($count > 0) {
			$results = array_slice($results, 0, $count);
		}

		return $results;
	}

	/**
	*
	*/
	public function enumerateSchedules($count = 0) {
		$log = App_Log::getInstance(self::IDENT);

		$results = array();

		$audit = new Audit($this->_id);
		$log->debug(sprintf('Enumerating schedules for audit with ID "%s"', $audit->id));

		$baseDate = new Zend_Date;
		$schedule = $audit->getSchedule();

		if (!isset($schedule['enableScheduling'])) {
			$log->info(sprintf('No enableScheduling key was set for audit with ID "%s"', $audit->id));
			return array();
		} else if ($schedule['enableScheduling'] == 'doesNotRepeat') {
			$log->info('The audit is configured to not repeat');
			return array();
		}

		$rangeStart = $this->_getRangeStart($schedule);
		$rangeEnd = $this->_getRangeEnd($schedule);

		$results[] = $rangeStart->get(Zend_Date::W3C);

		while($rangeStart->isEarlier($rangeEnd)) {
			switch($schedule['enableScheduling']) {
				case 'daily':
					$results[] = $rangeStart->get(Zend_Date::W3C);
					$rangeStart = $rangeStart->addDay($schedule['repeatEvery']);
					break;
				case 'everyWeekday':
					$day = $rangeStart->get(Zend_Date::WEEKDAY_DIGIT);

					/**
					* 0 = Sunday
					* 6 = Saturday
					*/
					$days = array(0, 6);
					if (in_array($day, $days)) {
						break;
					} else {
						$results[] = $rangeStart->get(Zend_Date::W3C);
						$rangeStart = $rangeStart->addDay(1);
					}
					break;
				case 'everyMonWedFri':
					$day = $rangeStart->get(Zend_Date::WEEKDAY_DIGIT);

					/**
					* 0 = Sunday
					* 2 = Tuesday
					* 4 = Thursday
					* 6 = Saturday
					*/
					$days = array(0, 2, 4, 6);
					if (in_array($day, $days)) {
						break;
					} else {
						$results[] = $rangeStart->get(Zend_Date::W3C);
						$rangeStart = $rangeStart->addDay(1);
					}
					break;
				case 'everyTueThu':
					$day = $rangeStart->get(Zend_Date::WEEKDAY_DIGIT);

					/**
					* 0 = Sunday
					* 1 = Monday
					* 3 = Wednesday
					* 5 = Friday
					* 6 = Saturday
					*/
					$days = array(0, 1, 3, 5, 6);
					if (in_array($day, $days)) {
						break;
					} else {
						$results[] = $rangeStart->get(Zend_Date::W3C);
						$rangeStart = $rangeStart->addDay(1);
					}
					break;
				case 'weekly':
					$rangeStart = $rangeStart->setWeekday(7);

					// Makes an array of numbers, starting from 0 (Sunday) to 6 (Saturday)
					$repeatOn = explode(',',$schedule['repeatOn']);

					foreach($repeatOn as $dow) {
						if ($dow == 0) {
							$dow = 7;
						}
						$rangeStart = $rangeStart->setWeekday($dow);
						$results[] = $rangeStart->get(Zend_Date::W3C);
					}

					// Sunday = 7 when using setWeekday
					$rangeStart = $rangeStart->setWeekday(7);
					$rangeStart = $rangeStart->addWeek(1);
					break;
				case 'monthly':
					// TODO: Make monthly scanning support absolute and relative days
					$results[] = $rangeStart->get(Zend_Date::W3C);
					$rangeStart = $rangeStart->addMonth($schedule['repeatEvery']);
					break;
				case 'yearly':
                                        $results[] = $rangeStart->get(Zend_Date::W3C);
                                        $rangeStart = $rangeStart->addYear($schedule['repeatEvery']);
					break;
				default:
					throw new Audit_Exception('Unknown scheduling type specified');
			}
		}

		$results[] = $rangeStart->get(Zend_Date::W3C);
		sort($results);
		$results = array_unique($results);

		if ($count > 0) {
			$results = array_slice($results, 0, $count);
		}

		return $results;
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
			/**
			* I am setting "never" to the end of the week because
			* otherwise setting it to a value too far in the future
			* can cause enumerating of the schedules to take waaaay
			* too long
			*/
			$date = new Zend_Date;

			$date->set('00:00:00', Zend_Date::TIMES);

			// Day 6 is Saturday
			$date->set(6, Zend_Date::WEEKDAY_DIGIT);
			$endDate = $date->get(Zend_Date::W3C);
			$end = new Zend_Date($endDate, Zend_Date::W3C);
		} else {
			$end = $rangeEnd->setDate($schedule['rangeEnd'], 'MM/dd/YYYY');
		}

		$end = $end->setTime($schedule['startOnTime'], 'HH:mma');

		return $end;
	}

	protected function _weekdaysInMonth($weekday, $month = null) {
		if ($month instanceof Zend_Date) {
			$date = $month;
		} else if (strlen($month) == 3) {
			$date = new Zend_Date;
			$date->setMonth($month, Zend_Date::MONTH_NAME_SHORT);
		} else if (is_numeric($month)) {
			$date = new Zend_Date;
			$date->setMonth($month, Zend_Date::MONTH_SHORT);
		} else if ((strlen($month) == 1 ) && !is_numeric($month)) {
			$date = new Zend_Date;
			$date->setMonth($month, Zend_Date::MONTH_NAME_NARROW);
		} else {
			$date = new Zend_Date;
		}

		$daysInMonth = $date->get(Zend_Date::MONTH_DAYS);
		$weekdays = array();

		for ($x = 1; $x <= $daysInMonth; $x++) {
			$date->setDay($x);
			$day = $date->get(Zend_Date::WEEKDAY_SHORT);

			if (isset($weekdays[$day])) {
				$weekdays[$day] += 1;
			} else {
				$weekdays[$day] = 1;
			}
		}

		if (isset($weekdays[$weekday])) {
			return $weekdays[$weekday];
		} else {
			throw new Exception('The specified weekday was not found');
		}
	}

	protected function _getNthOccurrence($day, $occurrence, $month = null) {
		if ($month instanceof Zend_Date) {
			$date = $month;
		} else if (strlen($month) == 3) {
			$date = new Zend_Date;
			$date->setMonth($month, Zend_Date::MONTH_NAME_SHORT);
		} else if (is_numeric($month)) {
			$date = new Zend_Date;
			$date->setMonth($month, Zend_Date::MONTH_SHORT);
		} else if ((strlen($month) == 1 ) && !is_numeric($month)) {
			$date = new Zend_Date;
			$date->setMonth($month, Zend_Date::MONTH_NAME_NARROW);
		} else {
			$date = new Zend_Date;
		}

		if ($occurrence == 'last') {
			$occurrence = -1;
		}

		$weekdaysInMonth = weekdaysInMonth($day, $date);
		$daysInMonth = $date->get(Zend_Date::MONTH_DAYS);
		$weekdays = array();

		for ($x = 1; $x <= $daysInMonth; $x++) {
			$date->setDay($x);
			$dayShort = $date->get(Zend_Date::WEEKDAY_SHORT);

			if (isset($weekdays[$dayShort])) {
				$weekdays[$dayShort] += 1;
			} else {
				$weekdays[$dayShort] = 1;
			}

			if (!isset($weekdays[$day])) {
				continue;
			} else if ($weekdays[$day] == $occurrence) {
				return $date->get(Zend_Date::DAY);
			} else if (($weekdays[$day] == $weekdaysInMonth) && ($occurrence == -1)) {
				return $date->get(Zend_Date::DAY);
			}
		}

		return 0;
	}
}

?>
