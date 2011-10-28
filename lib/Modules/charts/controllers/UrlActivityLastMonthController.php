<?php

/**
* @author Tim Rupp
*/
class Charts_UrlActivityLastMonthController extends Charts_Abstract {
	const IDENT = __CLASS__;

	public function indexAction() {
		$date = new Zend_Date;
		$this->view->assign(array(
			'today' => $date
		));
	}

	public function chartAction() {
		$status = false;
		$message = null;
		$top = 0;
		$lables = array();

		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$request = $this->getRequest();
		$request->setParamSources(array('_GET'));

		$format = $request->getParam('format');

		$log->debug('Building chart "URL Activity Last Month (bar)"');

		try {
			$sql = $db->select()
				->from('urls', array(
					'dpday' => "DATE_PART('day', created_at)",
					'count' => 'COUNT(*)'
				))
				->where("created_at >= (now() - interval '1 month')")
				->group(array('dpday'))
				->order(array('dpday ASC'));

			$log->debug($sql->__toString());

			$stmt = $sql->query();
			$result = $stmt->fetchAll();

			if (empty($result)) {
				throw new Exception('No results were found for the "Activity Last Month Bar" graph');
			}

			// Finds the highest url count value
			foreach($result as $val) {
				if ($val['count'] > $top) {
					$top = $val['count'];
				}
			}

			$date = new Zend_Date;
			$totalDays = $date->get(Zend_Date::MONTH_DAYS);
			$message = array_fill(1,$totalDays,0);
			$label = array_fill(1,$totalDays,0);

			foreach($result as $val) {
				$tmp = ($val['count'] / $top) * 100;
				$count = round($tmp);

				$hour = (int)$val['dpday'];
				$message[$hour] = $count;
				$label[$hour] = $val['count'];
			}

			for ($x = 1; $x <= $totalDays; $x++) {
				$labels[] = $x;
			}

			$status = true;
		} catch (Exception $error) {
			$log->err($error->getMessage());
			$message = $error->getMessage();
		}

		$this->view->response = array(
			'status' => $status,
			'message' => $message,
			'format' => $format
		);

		if (isset($label)) {
			$this->view->response['label'] = $label;
		}

		if (isset($labels)) {
			$this->view->response['labels'] = $labels;
		}
	}

	public function downloadAction() {
		$csvData = '';

		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$request = $this->getRequest();
		$request->setParamSources(array('_GET'));

		$format = $request->getParam('format');
		$filename = 'report.csv';

		$log->debug('Building chart "URL Activity Last Month (bar)"');

		try {
			$sql = $db->select()
				->from('urls', array(
					'dpday' => "DATE_PART('day', created_at)",
					'dpmon' => "DATE_PART('month', created_at)",
					'count' => 'COUNT(*)'
				))
				->where("created_at >= (now() - interval '1 month')")
				->group(array('dpday', 'dpmon'))
				->order(array('dpmon ASC', 'dpday ASC'));

			$log->debug($sql->__toString());

			$stmt = $sql->query();
			$result = $stmt->fetchAll();

			if (empty($result)) {
				throw new Exception('No results were found for the "Activity Last Month Bar" graph');
			}

			$date = new Zend_Date;
			$totalDays = $date->get(Zend_Date::MONTH_DAYS);

			foreach($result as $val) {
				$day = (int)$val['dpday'];
				$month = (int)$val['dpmon'];
				$message[$month][$day] = $val['count'];
			}

			$status = true;
		} catch (Exception $error) {
			$log->err($error->getMessage());
			$message = $error->getMessage();
		}

		$csvData = "month,dayOfMonth,Count\n";

		foreach($message as $month => $day) {
			foreach ($day as $key => $count) {
				$csvData .= sprintf("%s,%s,%s\n", $month, $key, $count);
			}
		}

		$options = array(
			'modified' => $date->get(Zend_Date::TIMESTAMP),
			'disposition' => 'attachment',
			'cache' => array(
				'must-revalidate' => true,
				'no-store' => true
			)
		);

		Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->setNoRender(true);
		$this->_helper->sendFile->sendData($csvData, 'text/plain', $filename, $options);
	}
}

?>
