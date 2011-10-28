<?php

/**
* @author Tim Rupp
*/
class Charts_UrlActivityLastWeekController extends Charts_Abstract {
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

		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$log->debug('Building chart "URL Activity Last Week (scatter)"');

		try {
			$sql = $db->select()
				->from('urls', array(
					'dpdow' => "DATE_PART('dow', created_at)",
					'dphour' => "DATE_PART('hour', created_at)",
					'count' => 'COUNT(*)'
				))
				->where("created_at >= (now() - interval '1 week')")
				->group(array('dphour', 'dpdow'))
				->order(array('dpdow ASC', 'dphour ASC'));

			$log->debug($sql->__toString());

			$stmt = $sql->query();
			$result = $stmt->fetchAll();

			if (empty($result)) {
				throw new Exception('No results were found for the "Activity Last Week Scatter" graph');
			}

			// Finds the highest url count value
			foreach($result as $val) {
				if ($val['count'] > $top) {
					$top = $val['count'];
				}
			}

			$data = array(array(0,0,1));
			foreach($result as $val) {
				$tmp = ($val['count'] / $top) * 100;
				$count = round($tmp);

				if ($count < 1) {
					$count = 1;
				}

				if ($count < 10) {
					continue;
				}
				// Adding 1 here so that it lifts the dots off of the axis
				// in the image
				$val['dpdow'] += 1;
				$val['dphour'] += 1;

				$data[] = array((int)$val['dphour'],(int)$val['dpdow'],(int)$count);
			}

			$message = array(
				'data' => $data
			);
			$status = true;
		} catch (Exception $error) {
			$log->err($error->getMessage());
			$message = $error->getMessage();
		}

		$this->view->response = array(
			'status' => $status,
			'message' => $message
		);
	}
}

?>
