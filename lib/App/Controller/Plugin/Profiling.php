<?php

/**
* @author Tim Rupp
*/
class App_Controller_Plugin_Profiling extends Zend_Controller_Plugin_Abstract {
	const IDENT = __CLASS__;

	protected $startTime = 0;

	public function preDispatch(Zend_Controller_Request_Abstract $request) {
		$this->startTime = microtime(true);
	}

	public function postDispatch(Zend_Controller_Request_Abstract $request) {
		$stopTime = 0;
		$runTime = 0;

		$log = App_Log::getInstance(self::IDENT);

		$stopTime = microtime(true);
		$runTime = $stopTime - $this->startTime;

		$log->debug(sprintf('Controller dispatch runtime was "%s" seconds', round($runTime, 4)));
	}
}

?>
