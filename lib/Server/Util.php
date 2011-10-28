<?php

/**
* @author Tim Rupp
*/
class Server_Util {
	const IDENT = __CLASS__;

	public function exists($scannerId) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('scanners')
			->where('id = ?', $scannerId)
			->limit(1);

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();

			if (count($stmt->fetchAll()) > 0) {
				return true;
			} else {
				return false;
			}
		} catch (Exception $error) {
			$log->err($error->getMessage());
			throw new Exception($error->getMessage());
		}
	}

	public function create() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);
		$permissions = new Permissions;

		$record = array(
			'name' => 'New scanner'
		);

		try {
			$log->debug('Inserting scanner record into database');

			$result = $db->insert('scanners', $record);

			if ($result > 0) {
				$log->debug('Inserted scanner record into the database');
				$scannerId = $db->lastInsertId('scanners_id');

				$result = $permissions->add('Scanner', $scannerId);
				return $scannerId;
			} else {
				$log->err('Failed to insert the new scanner record into the database');
				return false;
			}
		} catch (Exception $error) {
			throw new Exception($error->getMessage());
		}
	}

	public static function hasServers() {
		$servers = self::getServers(1,0);
		if (count($servers) > 0) {
			return true;
		} else {
			return false;
		}
	}
}

?>
