<?php

/**
* Check to see if safe_mode is enabled.
*
* @author Tim Rupp
*/
class Maintenance_Plugin_CheckSafeMode extends Maintenance_Plugin_Abstract {
	const IDENT = __CLASS__;

	public function dispatch(Maintenance_Request_Abstract $request) {
		$log = App_Log::getInstance(self::IDENT);

		$log->debug("Checking to see if PHP's safe mode is enabled");

		$result = ini_get('safe_mode');

		if ($result === true) {
			$log->info('PHP safe mode is enabled');
			return true;
		} else {
			$log->info('PHP safe mode is not enabled');
			return false;
		}
	}
}

?>
