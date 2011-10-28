<?php

class Plugin_Util {
	const IDENT = __CLASS__;

	public static function getIds() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance();
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()->from('plugins', 'id');

		$stmt = $sql->query();
		$results = $stmt->fetchAll();
		return $results;
	}

	public static function getFamily($pluginId) {
		$return = array();

		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance();
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('plugins', 'family')
			->where('id = ?', $pluginId)
			->limit(1);

		$stmt = $sql->query();
		$results = $stmt->fetch();
		if (isset($results['family'])) {
			return trim($results['family']);
		} else {
			return null;
		}
	}

	public static function getFamilies() {
		$return = array();

		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance();
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('plugins_families')
			->where('family != ?', 'Port scanners');

		$stmt = $sql->query();
		$results = $stmt->fetchAll();
		foreach($results as $result) {
			$return[] = $result['family'];
		}

		return $return;
	}
}

?>
