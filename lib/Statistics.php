<?php

/**
* @author Tim Rupp
*/
class Statistics {
	public function calculateUpcoming($account, $start, $end) {
		$results = array();

		// Dates may be send to us in milliseconds, so convert them to seconds
		if (is_numeric($start)) {
			$startTs = $start / 1000;
			$start = new Zend_Date($startTs, Zend_Date::TIMESTAMP);
		}

		if (is_numeric($end)) {
			$endTs = $end / 1000;
			$end = new Zend_Date($end, Zend_Date::TIMESTAMP);
		}

		$audits = $account->audit->getUpcomingAudits($start, $end);

		if (!empty($audits)) {
			foreach($audits as $audit) {
				$className = '';
				$start = new Zend_Date($audit['date_scheduled'], Zend_Date::ISO_8601);
				$status = trim($audit['status']);

				if ($status == 'P') {
					$className = 'pendingEvent';
				} else if ($status == 'F' || $status = 'N') {
					$className = 'finishedEvent';
				}

				$results[] = array(
					'id' => $audit['id'],
					'title' => $audit['name'],
					'allDay' => false,
					'start' => $start->get(Zend_Date::W3C),
					'className' => $className
				);
			}
		}

		return $results;
	}
}

?>
