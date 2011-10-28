<?php

/**
* @author Tim Rupp
*/
class Audit_Plugins_Family {
	/**
	*
	*/
	const IDENT = __CLASS__;

	public static function exists($family) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->plugin);

		$sql = $db->select()
			->from('plugins')
			->where('family = ?', $family);

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			$result = $stmt->fetchAll();

			if (count($result) > 0) {
				return true;
			} else {
				return false;
			}
		} catch (Exception $error) {
			throw new Audit_Plugins_Exception($error->getMessage());
		}
	}
}

?>
