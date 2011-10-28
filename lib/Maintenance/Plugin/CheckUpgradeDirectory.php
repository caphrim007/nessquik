<?php

/**
* Check if the upgrade directory exists
*
* This is another potential problem that a baddie
* could abuse. Better off to check to see if it
* exists and maybe stop nessquik from going any
* further if it does.
*
*
* @author Tim Rupp
*/
class Maintenance_Plugin_CheckUpgradeDirectory extends Maintenance_Plugin_Abstract {
	const IDENT = __CLASS__;

	public function dispatch(Maintenance_Request_Abstract $request) {
		$log = App_Log::getInstance(self::IDENT);

		$log->info("Checking to see if your upgrade directory exists");

		if (file_exists(_ABSPATH.'/opt/upgrade/')) {
			$log->warn('Your upgrade directory still exists. Please remove it before using nessquik');
			return true;
		} else {
			return false;
		}
	}
}

?>
