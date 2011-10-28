<?php

/**
* API wrapper around the Maintenance class
*
* This class wraps other classes that provide the
* functionality exposed through the maintenance namespace
*
* @author Tim Rupp
*/
class Api_Maintenance {
	const IDENT = __CLASS__;

	/**
	* @param string $token Access token
	* @return boolean
	*/
	public function cleanTokens($token) {
		$log = App_Log::getInstance(self::IDENT);

		try {
			$controller = Maintenance_Controller_Front::getInstance();
			$controller->registerPlugin(new Maintenance_Controller_Plugin_CleanTokens);
			$controller->dispatch();
			return true;
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}
}

?>
