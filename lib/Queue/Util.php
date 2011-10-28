<?php

/**
* @author Tim Rupp
*/
class Queue_Util {
	const IDENT = __CLASS__;

	public static function exists($queue) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('queue')
			->limit(1);

		if (is_numeric($queue)) {
			$sql->where('queue_id = ?', $queue);
		} else {
			$sql->where('queue_name = ?', $queue);
		}

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
			$log->err($error->getMessage());
			return false;
		}
	}

	public static function getId($name) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('queue', array('queue_id'))
			->where('queue_name = ?', $name);

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			$result = $stmt->fetchAll();

			if (count($result) > 0) {
				return $result[0]['queue_id'];
			} else {
				return 0;
			}
		} catch (Exception $error) {
			throw new Exception($error->getMessage());
		}
	}

	/**
	* @throws Exception
	* @return integer
	*/
	public static function create($name = null, $description = null) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);
		$date = new Zend_Date;

		$name = trim($name);
		if (is_numeric(substr($name, 0, 1))) {
			throw new Exception('Queue name cannot start with a number');
		}

		if (self::exists($name)) {
			throw new Exception(sprintf('The queue "%s" already exists'), $name);
		}

		$data = array(
			'queue_name' => $name
		);

		$result = $db->insert('queue', $data);
		if (!empty($result)) {
			$permissions = new Permissions;
			$queueId = $db->lastInsertId('queue_queue_id');
			$result = $permissions->add('Queue', $queueId);

			if ($result === false) {
				throw new Exception('Failed to add the queue to the permissions list');
			} else {
				return $queueId;
			}
		} else {
			throw new Exception('Failed to insert the new queue');
		}
	}
}

?>
