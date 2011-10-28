<?php

/**
* @author Tim Rupp
*/
class App_Controller_Plugin_InitCache extends Zend_Controller_Plugin_Abstract {
	const IDENT = __CLASS__;

	public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request) {
		$config = Ini_Cache::getInstance();

		if (isset($config->translate->cache_dir)) {
			$cache = Zend_Cache::factory('Core',
				$config->translate->backend,
				$config->frontend->toArray(),
				$config->translate->toArray()
			);

			Zend_Translate::setCache($cache);
		}
	}
}

?>
