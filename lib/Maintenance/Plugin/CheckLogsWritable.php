<?php

/**
* @author Tim Rupp
*/
class Maintenance_Plugin_CheckLogsWritable extends Maintenance_Plugin_Abstract {
	const IDENT = __CLASS__;

	public function dispatch(Maintenance_Request_Abstract $request) {
		$log = App_Log::getInstance(self::IDENT);
		$config = Ini_Config::getInstance();

		$log->info('Checking to see if your log directory is writable');

		if (is_writable(_ABSPATH.'/var/log/')) {
			return true;
		} else {
			return false;
		}
	}
}

?>
