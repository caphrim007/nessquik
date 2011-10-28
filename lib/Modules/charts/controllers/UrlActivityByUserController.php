<?php

/**
* @author Tim Rupp
*/
class Charts_UrlActivityByUserController extends Charts_Abstract {
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
		$tmp = array();
		$top = 0;

		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		try {
			$sql = $db->select()
				->from('urls', array(
					'dpdow' => "DATE_PART('dow', created_at)",
					'count' => 'COUNT(*)',
					'account_id' => 'account_id'
				))
				->joinLeft('accounts' , 'urls.account_id = accounts.id', array('username'))
				->where("created_at >= (now() - interval '1 week')")
				->group(array('account_id','username','dpdow'))
				->order(array('username ASC'));

			$log->debug($sql->__toString());

			$stmt = $sql->query();
			$result = $stmt->fetchAll();

			if (empty($result)) {
				throw new Exception('No results were found for the "Activity By Account Last Week Stacked" graph');
			}

			foreach($result as $key => $val) {
				if ($val['count'] > $top) {
					$top = $val['count'];
				}
			}

			foreach($result as $val) {
				$username = $val['username'];
				$index = $val['dpdow'];

				$message[$username]['label'] = $username;
				$message[$username]['points'][$index] = round(($val['count'] / $top) * 100);
			}

			foreach($message as $msg) {
				$label = $msg['label'];

				if (!isset($message[$label]['points'][0])) {
					$message[$label]['points'][0] = 0;
				} else if (!isset($message[$label]['points'][6])) {
					$message[$label]['points'][6] = 0;
				}

				$message[$label]['points'] = $this->array_setkeys($message[$label]['points'], 0);
				ksort($message[$label]['points']);
			}

			unset($result);

			foreach($message as $key => $val) {
				$result[] = array(
					'label' => $val['label'],
					'points' => $val['points']
				);
			}

			$status = true;
			$message = array(
				'result' => $result,
				'top' => $top
			);
		} catch (Exception $error) {
			$message = $error->getMessage();
			$log->err($message);
		}

		$this->view->response = array(
			'status' => $status,
			'message' => $message
		);
	}

	protected function array_setkeys($array, $fill = null) {
		$indexMax = -1;

		ksort($array);
		end($array);
		$indexMax = key($array);

		for ($i = 0; $i <= $indexMax; $i++) {
			if (!isset($array[$i])) {
				$array[$i] = $fill;
			}
		}

		ksort($array);
		return $array;
	}
}

?>
