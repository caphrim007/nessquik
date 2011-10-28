<?php

/**
* @author Tim Rupp
*/
class Tables_FaviconPanoramaController extends Tables_Abstract {
	const IDENT = __CLASS__;

	public function indexAction() {
		$results = array();

		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$couch = new Phly_Couch($config->database->couch->params);

		$request = $this->getRequest();
		$request->setParamSources(array('_GET'));

		$startTime = $request->getParam('from');
		$endTime = $request->getParam('to');

		$docs = $couch->view('metrics', 'faviconList', array(
			'descending' => false,
			'limit' => 200
		));

		try {
			$results = $docs;
			$status = true;
		} catch (Exception $error) {
			$log->err($error->getMessage());
			$results = array();
		}

		$this->view->assign(array(
			'results' => $results
		));
	}

	public function tableAction() {

	}

	public function similarAction() {

	}
}

?>
