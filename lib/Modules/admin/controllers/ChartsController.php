<?php

/**
* @author Tim Rupp
*/
class Admin_ChartsController extends Zend_Controller_Action {
	public $session;

	const IDENT = __CLASS__;

	public function init() {
		parent::init();

		$config = Ini_Config::getInstance();
		$auth = Zend_Auth::getInstance();

		$sessionUser = $auth->getIdentity();
		$sessionId = Account_Util::getId($sessionUser);
		$this->session = new Account($sessionId);
		$request = $this->getRequest();

		if ($this->session->isFirstBoot()) {
			$this->_redirector = $this->_helper->getHelper('Redirector');
			$this->_redirector->gotoSimple('index', 'index', 'start');
		}

		if (!$this->session->acl->isAllowed('Capability', array('admin_operator', 'view_charts'))) {
			$this->_redirector = $this->_helper->getHelper('Redirector');
			$this->_redirector->gotoSimple('permission-denied', 'error', 'default');
		}

		$this->view->assign(array(
			'action' => $request->getActionName(),
			'config' => $config,
			'controller' => $request->getControllerName(),
			'module' => $request->getModuleName(),
			'session' => $this->session
		));
	}

	public function indexAction() {
		$this->view->assign(array(
			'page' => 1
		));
	}

	public function countryExposureAction() {
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

				usort($results, array($this, 'sortDocs'));

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

	public function urlActivityLastWeekScatterAction() {
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

	public function urlActivityLastDayBarAction() {
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

	public function urlActivityByAccountLastWeekStackedAction() {
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

	protected function sortDocs($a, $b) {
		if ($a['value'] == $b['value']) {
			return 0;
		}

		return ($a['value'] < $b['value']) ? 1 : -1;
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

	public function urlActivityLastMonthBarAction() {
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
}

?>
