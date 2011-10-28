<?php

/**
* @author Tim Rupp
*/
class Reports_BlackholeReconcileController extends Reports_Abstract {
	const IDENT = __CLASS__;

	public function indexAction() {
		$selectedTags = array();
		$filter = new Zend_Filter_Boolean(Zend_Filter_Boolean::ALL);

		$config = Ini_Config::getInstance();
		$couch = new Phly_Couch($config->database->couch->params);

		$docs = $couch->view('reports', 'blackholeReconcileHistory');
		if (count($docs) == 0) {
			$tmp = array(
				'lastReconcile' => null
			);
			$status = $this->saveConfig($tmp);
			$this->reloadConfig();
		}
	}

	public function createAction() {

	}

	public function reportAction() {
		$status = false;
		$message = null;
		$date = new Zend_Date;
		$totalFixedUrls = 0;
		$totalFailedFixed = 0;
		$totalRemovedUrls = 0;
		$totalFailedRemoved = 0;
		$totalFailed = 0;
		$removedList = array();
		$failedRemovedList = array();
		$fixedList = array();
		$failedFixedList = array();
		$data = array(
			'created' => $date->get(Zend_Date::W3C),
			'totalRemovedUrls' => 0,
			'totalFixedUrls' => 0,
			'totalFailedFixed' => 0,
			'totalFailedRemoved' => 0,
			'removedList' => array(),
			'failedRemovedList' => array(),
			'fixedList' => array(),
			'failedFixedList' => array(),
			'whoRemoved' => null,
			'docType' => 'BlackholeReconcile'
		);

		$log = App_Log::getInstance(self::IDENT);
		$config = Ini_Config::getInstance();
		$couch = new Phly_Couch($config->database->couch->params);
		$account = $this->_helper->GetRequestedAccount();

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$reinstallUrls = $request->getParam('reinstall-url');
		$removeUrls = $request->getParam('remove-url');

		try {
			if (!empty($reinstallUrls)) {
				foreach($reinstallUrls as $urlId) {
					$url = new Url($urlId);
					$result = $url->blackhole->create();

					if ($result === true) {
						$totalFixedUrls += 1;
						$fixedList[] = $url->uri;
					} else {
						$totalFailedFixed += 1;
						$failedFixedList[] = $url->uri;
					}
				}
			}

			if (!empty($removeUrls)) {
				foreach($removeUrls as $urlId) {
					$url = new Url($urlId);
					$result = $url->blackhole->delete();
					if ($result === true) {
						$totalRemovedUrls += 1;
						$removedList[] = $url->uri;
					} else {
						$totalFailedRemoved += 1;
						$failedRemovedList[] = $url->uri;
					}
				}
			}

			$data['whoRemoved'] = $account->getUsername();
			$data['totalRemovedUrls'] = $totalRemovedUrls;
			$data['totalFixedUrls'] = $totalFixedUrls;
			$data['totalFailedFixed'] = $totalFailedFixed;
			$data['totalFailedRemoved'] = $totalFailedRemoved;
			$data['removedList'] = $removedList;
			$data['failedRemovedList'] = $failedRemovedList;
			$data['fixedList'] = $fixedList;
			$data['failedFixedList'] = $failedFixedList;

			$totalFailed = $totalFailedFixed + $totalFailedRemoved;

			$doc = new Phly_Couch_Document($data);
			$result = $couch->docSave($doc);

			$tmp = array(
				'lastReconcile' => $date->get(Zend_Date::W3C)
			);
			$status = $this->saveConfig($tmp);

			if ($totalFailed > 0) {
				$status = false;
				$this->view->assign(array(
					'totalFailedFixed' => $totalFailedFixed,
					'totalFailedRemoved' => $totalFailedRemoved,
					'failedRemovedList' => $failedRemovedList,
					'failedFixedList' => $failedFixedList
				));

				$message = $this->view->render('blackhole-reconcile/failed-list.phtml');
				$this->view->clearVars();

				$status = false;
			} else {
				$status = true;
			}
		} catch (Exception $error) {
			$status = false;
			$message = $error->getMessage();
			$log->err($message);
		}

		$this->view->response = array(
			'status' => $status,
			'message' => $message
		);
	}

	public function historyAction() {
		$config = Ini_Config::getInstance();
		$couch = new Phly_Couch($config->database->couch->params);

		$docs = $couch->view('reports', 'blackholeReconcileHistory');
		if (count($docs) == 0) {
			$results = array();
		} else {
			$results = $docs;
		}

		$this->view->assign(array(
			'results' => $results
		));
	}

	protected function refreshTableAction() {
		$urls = array();
		$infoblox = array();
		$log = App_Log::getInstance(self::IDENT);

		try {
			$urls = $this->_getBlackholeUrls();
			if (!empty($urls)) {
				foreach($urls as $url) {
					if ($this->_isReallyBlackholed($url)) {
						$infoblox[] = $url->id;
					}
				}
			}
		} catch (Exception $error) {
			$log->err($error->getMessage());
		}

		$this->view->assign(array(
			'urls' => $urls,
			'infoblox' => $infoblox
		));
	}

	protected function _getBlackholeUrls() {
		$config = Ini_Config::getInstance();
		$couch = new Phly_Couch($config->database->couch->params);

		$docs = $couch->view('urls', 'blackhole');
		if (count($docs) == 0) {
			$results = array();
		} else {
			foreach($docs as $doc) {
				$results[] = new Url($doc->key);
			}
		}

		return $results;
	}

	protected function _isReallyBlackholed(Url $url) {
		$response = array();

		try {
			$bhSearch = Url_Blackhole_Util::search($url->uri);
			if (!empty($bhSearch)) {
				return true;
			}
		} catch (Exception $error) {
			return false;
		}

		return false;
	}
}

?>
