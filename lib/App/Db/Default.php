<?php

/**
* @author Tim Rupp
*/
class App_Db_Default extends App_Db_Abstract {
	private static $instance;

	public static function getInstance() {
		if (empty(self::$instance)) {
			$config = Ini_Config::getInstance();
			$instance = $config->database->default;
			$instance = parent::setOptions($instance);

			$db = Zend_Db::factory($instance);
			$db->setFetchMode(App_Db::FETCHMODE);
			self::$instance = $db;
		}

		return self::$instance;
	}
}

?>
