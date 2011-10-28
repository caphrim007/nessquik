<?php

/**
* @author Tim Rupp
*/
class Charts_CountryExposureController extends Charts_Abstract {
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
		$couch = new Phly_Couch($config->database->couch->params);

		try {
			$documents = $couch->view('charts', 'countryExposure', array('group' => true));
			if ($documents instanceof Phly_Couch_DocumentSet) {
				$tmp = $documents->toArray();
				$results = $tmp['docs'];
				if (empty($results)) {
					throw new Exception('There were no values found in the country exposure view');
				} else {
					$status = true;
				}

				$results = $this->_helper->SortChartDocs($results);

				// Use this as our 100% value
				$top = $results[0]['value'];

				foreach($results as $result) {
					$cc = $result['key'];
					$count = ($result['value'] / $top) * 100;
					$message[$cc] = $count;
				}
			}
		} catch (Exception $error) {
			$status = false;
			$log->err($error->getMessage());
			$message = $error->getMessage();
		}

		$this->view->response = array(
			'status' => $status,
			'message' => $message
		);
	}

	public function tableAction() {
		$status = false;
		$message = null;

		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$couch = new Phly_Couch($config->database->couch->params);

		$documents = $couch->view('charts', 'countryExposure', array('group' => true, 'reduce' => true, 'descending' => false));
		if ($documents instanceof Phly_Couch_DocumentSet) {
			$tmp = $documents->toArray();
			$results = $tmp['docs'];
			if (empty($results)) {
				throw new Exception('There were no values found in the country exposure view');
			} else {
				$status = true;
			}
		}

		$this->view->assign(array(
			'results' => $results
		));
	}
}

?>
