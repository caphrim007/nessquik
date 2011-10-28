<?php

/**
* @author Tim Rupp
*/
abstract class Maintenance_Plugin_Abstract {
	/**
	* Called before Maintenance_Engine begins evaluating the
	* request against its routes.
	*
	* @param Maintenance_Request_Abstract $request
	* @return void
	*/
	public function maintenanceStartup(Maintenance_Request_Abstract $request){

	}

	/**
	* Called upon Maintenance_Engine shutdown; after all tasks have run.
	*
	* @param  Maintenance_Request_Abstract $request
	* @return void
	*/
	public function maintenanceShutdown(Maintenance_Request_Abstract $request) {

	}

	/**
	* @return void
	*/
	public function dispatch(Maintenance_Request_Abstract $request) {

	}
}

?>
