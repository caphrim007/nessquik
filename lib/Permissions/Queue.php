<?php

/**
* @author Tim Rupp
*/
class Permissions_Queue extends Permissions_Abstract {
	const IDENT = __CLASS__;

	/**
	* @throws Permissions_Exception
	* @return boolean
	*/
	public function add($queue) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		try {
			$data = array(
				'resource' => $queue
			);
			$db->insert('permissions_queue', $data);
			return true;
		} catch (Exception $error) {
			throw new Permissions_Exception($error->getMessage());
		}

		return false;
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
			$db->delete('permissions_queue', $where);
			return true;
		} catch (Exception $error) {
			throw new Permissions_Exception($error->getMessage());
		}
	}

	/**
	* @throws Permissions_Exception
	* @return boolean
	*/
	public function exists($queue) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select('id')
			->from('permissions_queue')
			->where(sprintf('%s = %s',
				$db->quoteIdentifier('resource'),
				$db->quote($queue)
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
			->from('permissions_queue', array('permission_id' => 'id', 'permission_resource' => 'resource'))
			->joinLeft('queue', 'permissions_queue.resource = queue.queue_id', array(
				'queue_id',
				'queue_name',
				'timeout',
				'desc'
			))
			->order('queue.queue_name ASC');

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
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('permissions_queue', array('permission_id' => 'id', 'permission_resource' => 'resource'))
			->joinLeft('queue', 'permissions_queue.resource = queue.queue_id', array(
				'queue_id',
				'queue_name',
				'timeout',
				'desc'
			))
			->order('queue.queue_name ASC');

		if ($resource === null) {
			$sql->order('resource ASC');
		} else {
			$sql->where('resource = ?', $resource);
		}

		if ($pattern !== null) {
			$sql->where('queue_name LIKE ?', $pattern.'%');
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
	*/
	public function getId($target) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select('id')
			->from('permissions_queue')
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
