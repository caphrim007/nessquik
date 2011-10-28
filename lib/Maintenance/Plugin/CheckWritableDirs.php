<?php

/**
* Check to see if the cache directory is writable
*
* @author Tim Rupp
*/
class Maintenance_Plugin_CheckWritableDirs extends Maintenance_Plugin_Abstract {
	const IDENT = __CLASS__;

	public function dispatch(Maintenance_Request_Abstract $request) {
		$log = App_Log::getInstance(self::IDENT);
		$config = Ini_Config::getInstance();

		$log->info("Checking to see if your cache directory is writable");
		if (is_writable(_ABSPATH.'/var/cache/')) {
			$log->info("Cache directory is writable");
		} else {
			$log->info("Cache directory is NOT writable");
		}

		$log->info("Checking to see if your tmp directory is writable");
		if (is_writable(_ABSPATH.'/tmp/')) {
			$log->info("Tmp directory is writable");
		} else {
			$log->info("Tmp directory is NOT writable");
		}

		$log->info("Checking to see if your local etc directory is writable");
		if (is_writable(_ABSPATH.'/etc/local')) {
			$log->info("Local etc directory is writable");
		} else {
			$log->info("Local etc directory is NOT writable");
		}
	}
}

?>
