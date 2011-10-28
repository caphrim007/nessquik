<?php

/**
* Singleton class for returning a database connection
* to the exemption database.
*
* @author Tim Rupp
*/
class App_Db_Exempt extends App_Db_Abstract {
	private static $instance;

	public static function getInstance() {
		if (empty(self::$instance)) {
			$config = Ini_Config::getInstance();
			$instance = $config->database->exempt;
			$instance = parent::setOptions($instance);

			$db = Zend_Db::factory($instance);
			$db->setFetchMode(App_Db::FETCHMODE);
			self::$instance = $db;
		}
		return self::$instance;
	}
}

?>
