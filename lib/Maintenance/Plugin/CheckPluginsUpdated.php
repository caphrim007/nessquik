<?php

/**
* @author Tim Rupp
*/
class Maintenance_Plugin_CheckPluginsUpdated extends Maintenance_Plugin_Abstract {
	const IDENT = __CLASS__;

	public function dispatch(Maintenance_Request_Abstract $request) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$log->debug('Checking to see if plugins are updated');

		$sql = $db->select()->from('plugins')->limit(2);

		$log->debug($sql->__toString());

		$stmt = $db->query($sql);
		$result = $stmt->fetchAll();

		if (count($result) < 2) {
			return false;
		} else {
			return true;
		}
	}
}

?>
