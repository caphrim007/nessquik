<?php

/**
* @author Tim Rupp
*/
class Tables_UrlAgeController extends Tables_Abstract {
	const IDENT = __CLASS__;

	public function indexAction() {
		$date = new Zend_Date;
		$yesterday = new Zend_Date;
		$yesterday = $yesterday->subDay(1);
		$oldest = $this->_getOldestUrl();
		$newest = $this->_getNewestUrl();

		$this->view->assign(array(
			'today' => $date,
			'oldest' => $oldest,
			'newest' => $newest,
			'yesterday' => $yesterday
		));
	}

	public function tableAction() {
		$results = array();
		$totalDocs = 0;

		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$couch = new Phly_Couch($config->database->couch->params);

		$request = $this->getRequest();
		$request->setParamSources(array('_GET'));

		$startTime = $request->getParam('from');
		$endTime = $request->getParam('to');

		$docs = $couch->view('urls', 'createdAt', array(
			'descending' => false,
			'startkey' => $startTime,
			'endkey' => $endTime,
			'include_docs' => true
		));

		try {
			$totalDocs = count($docs);
			if ($totalDocs > 0) {	
				foreach($docs as $doc) {
					$results[] = array(
						'createdAt' => $doc->doc['created_at'],
						'uri' => $doc->doc['uri'],
						'savoryId' => $doc->doc['savoryId']
					);
				}
			}

			$status = true;
		} catch (Exception $error) {
			$log->err($error->getMessage());
			$results = array();
		}

		$this->view->assign(array(
			'results' => $results,
			'totalDocs' => $totalDocs
		));
	}

	protected function _getOldestUrl() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$couch = new Phly_Couch($config->database->couch->params);

		$docs = $couch->view('urls', 'createdAt', array('descending' => false, 'limit' => 1));

		if (count($docs) > 0) {	
			$view = $docs->fetch(0);
			$docId = $view->value;

			$doc = $couch->docOpen($docId);
			$url = new Url($doc->savoryId);
			return $url;
		} else {
			return null;
		}
	}

	protected function _getNewestUrl() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$couch = new Phly_Couch($config->database->couch->params);

		$docs = $couch->view('urls', 'createdAt', array('descending' => true, 'limit' => 1));

		if (count($docs) > 0) {	
			$view = $docs->fetch(0);
			$docId = $view->value;

			$doc = $couch->docOpen($docId);
			$url = new Url($doc->savoryId);
			return $url;
		} else {
			return null;
		}
	}
}

?>
