<?php

/**
* @author Tim Rupp
*/
class App_Db {
	private static $instance;

	const FETCHMODE = Zend_Db::FETCH_ASSOC;

	static function getInstance($params) {
		if (!($params instanceof Zend_Config)) {
			$config = Ini_Config::getInstance();
			if ($config->database->default instanceof Zend_Config) {
				$params = $config->database->default;
			} else {
				throw new App_Exception('Neither the specified or default database credentials exist');
			}
		}

		$hash = md5(serialize($params));

		if (empty(self::$instance[$hash])) {
			try {
				$db = Zend_Db::factory($params);
				$db->setFetchMode(self::FETCHMODE);
				self::$instance[$hash] = $db;
			} catch (Exception $error) {
				$config = Ini_Config::getInstance();
				$params = $config->database->default;

				$db = Zend_Db::factory($params);
				$db->setFetchMode(self::FETCHMODE);
				$hash = md5(serialize($params));
				self::$instance[$hash] = $db;
			}
		}

		return self::$instance[$hash];
	}

	static function factory($params) {
		if (!($params instanceof Zend_Config)) {
			$config = Ini_Config::getInstance();
			if ($config->database->default instanceof Zend_Config) {
				$params = $config->database->default;
			} else {
				throw new App_Exception('Neither the specified or default database credentials exist');
			}
		}

		try {
			$db = Zend_Db::factory($params);
			$db->setFetchMode(self::FETCHMODE);
			return $db;
		} catch (Exception $error) {
			$config = Ini_Config::getInstance();
			$params = $config->database->default;

			$db = Zend_Db::factory($params);
			$db->setFetchMode(self::FETCHMODE);
			return $db;
		}
	}
}

?>
