<?php

/**
*
*/
class App_Db_Custom {
	public static function get($params) {
		if (empty(self::$instance)) {
			$db = Zend_Db::factory($params);
			$db->setFetchMode(App_Db::FETCHMODE);
		}
		return $db;
	}
}

?>
