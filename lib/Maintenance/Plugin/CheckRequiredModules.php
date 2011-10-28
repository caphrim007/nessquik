<?php

/**
* Check to see if required PHP modules are installed
*
* @author Tim Rupp
*/
class Maintenance_Plugin_CheckRequiredModules extends Maintenance_Plugin_Abstract {
	const IDENT = __CLASS__;

	public function dispatch(Maintenance_Request_Abstract $request) {
		$log = App_Log::getInstance(self::IDENT);

		$log->debug("Checking to see if required PHP modules are installed");
	}
}

?>
