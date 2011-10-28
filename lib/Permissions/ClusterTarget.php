<?php

/**
* @author Tim Rupp
*/
class Permissions_ClusterTarget extends Permissions_Abstract {
	const IDENT = __CLASS__;

	/**
	* @throws Permissions_Exception
	* @return boolean
	*/
	public function add($target) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		try {
			$data = array(
				'resource' => $target
			);
			$db->insert('permissions_cluster', $data);
			return true;
		} catch (Exception $error) {
			throw new Permissions_Exception($error->getMessage());
		}
	}

	/**
	* @throws Permissions_Exception
	*/
	public function delete($id) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		try {
			$where = $db->quoteInto('id = ?', $id);
			$db->delete('permissions_cluster', $where);
			return true;
		} catch (Exception $error) {
			throw new Permissions_Exception($error->getMessage());
		}
	}

	/**
	* @throws Permissions_Exception
	* @return boolean
	*/
	public function exists($target) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select('id')
			->from('permissions_cluster')
			->where(sprintf('%s = %s',
				$db->quoteIdentifier('resource'),
				$db->quote($target)
			))
			->limit(1);

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
			throw new Permissions_Exception($error->getMessage());
		}
	}

	/**
	* @throws Permissions_Exception
	* @return array
	*/
	public function get($resource = null, $page = 1, $limit = 15) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('permissions_cluster', array('permission_id' => 'id', 'permission_resource' => 'resource'));

		if ($resource === null) {
			$sql->order('resource ASC');
		} else {
			$sql->where('resource = ?', $resource);
		}

		if (!empty($limit)) {
			$sql->limitPage($page, $limit);
		}

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			$result = $stmt->fetchAll();
			return $result;
		} catch (Exception $error) {
			throw new Permissions_Exception($error->getMessage());
		}
	}

	/**
	* @throws Permissions_Exception
	* @return array
	*/
	public function getPattern($resource = null, $pattern = null, $page = 1, $limit = 15) {

	}

	/**
	* @throws Permissions_Exception
	*/
	public function getId($target) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select('id')
			->from('permissions_cluster')
			->where(sprintf('%s = %s',
				$db->quoteIdentifier('resource'),
				$db->quote($target)
			))
			->limit(1);

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			$result = $stmt->fetchAll();

			if (count($result) > 0) {
				return $result[0]['id'];
			} else {
				return 0;
			}
		} catch (Exception $error) {
			throw new Permissions_Exception($error->getMessage());
		}
	}
}

?>
