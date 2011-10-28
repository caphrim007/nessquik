<?php

/**
* @author Tim Rupp
*/
class Charts_UrlActivityLastDayController extends Charts_Abstract {
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

		try {
			$message = array();
			$label = array();
			$message = array_fill(0, 23, 0);
			$label = array_fill(0, 23, 0);

			$sql = $db->select()
				->from('urls', array(
					'dphour' => "DATE_PART('hour', created_at)",
					'count' => 'COUNT(*)'
				))
				->where("created_at >= (now() - interval '1 day')")
				->group(array('dphour'))
				->order(array('dphour ASC'));

			$log->debug($sql->__toString());

			$stmt = $sql->query();
			$result = $stmt->fetchAll();

			if (empty($result)) {
				throw new Exception('No results were found for the "Activity Last Day Bar" graph');
			}

			foreach($result as $val) {
				if ($val['count'] > $top) {
					$top = $val['count'];
				}
			}

			foreach($result as $val) {
				$tmp = ($val['count'] / $top) * 100;
				$count = round($tmp);

				$hour = $val['dphour'];
				$message[$hour] = $count;
				$label[$hour] = $val['count'];
			}

			$status = true;
		} catch (Exception $error) {
			$status = false;
			$log->err($error->getMessage());
			$message = $error->getMessage();
		}

		$this->view->response = array(
			'status' => $status,
			'message' => $message
		);

		if (isset($label)) {
			$this->view->response['label'] = $label;
		}
	}
}

?>
