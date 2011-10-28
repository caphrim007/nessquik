<?php

/**
* Check if the pages are being served over SSL
* This function uses the Apache server variable
* HTTPS to determine whether SSL is being used.
*
* @author Tim Rupp
*/
class Maintenance_Plugin_CheckSecureHttps extends Maintenance_Plugin_Abstract {
	const IDENT = __CLASS__;

	public function dispatch(Maintenance_Request_Abstract $request) {
		$log = App_Log::getInstance(self::IDENT);

		$log->debug("Checking to see if SSL is being used");

		if (isset($_SERVER['HTTPS'])) {
			$result = $_SERVER['HTTPS'];
			$result = strtolower($result);

			if ($result == "on") {
				$log->info('SSL is enabled');
				return true;
			} else {
				$log->info('SSL is disabled');
				return false;
			}
		} else {
			return false;
		}
	}
}

?>
