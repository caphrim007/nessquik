<?php

/**
* @author Tim Rupp
*/
class Maintenance_Plugin_CleanTokens extends Maintenance_Plugin_Abstract {
	const IDENT = __CLASS__;

	/**
	* @throws Maintenance_Plugin_Exception
	*/
	public function dispatch(Maintenance_Request_Abstract $request) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);
		$date = new Zend_Date;

		try {
			$where = $db->quoteInto('valid_to = ?', $date->get(Zend_Date::W3C));
			$db->delete('tokens', $where);
		} catch (Exception $error) {
			throw new Maintenance_Plugin_Exception($error->getMessage());
		}
	}
}

?>
