<?php

/**
* Check if the setup directory exists
*
* This has the potential to be a security issue
* after nessquik is installed, so if I check for
* its existence, I can alert the end user
*
* @author Tim Rupp
*/
class Maintenance_Plugin_CheckSetupDirectory extends Maintenance_Plugin_Abstract {
	const IDENT = __CLASS__;

	public function dispatch(Maintenance_Request_Abstract $request) {
		$log = App_Log::getInstance(self::IDENT);

		$log->info("Checking to see if your setup directory exists");

		if (file_exists(_ABSPATH.'/opt/setup/')) {
			$log->warn('Your setup directory still exists. Please remove it before using nessquik');
			return true;
		} else {
			return false;
		}
	}
}

?>
