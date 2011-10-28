<?php

/**
* @author Tim Rupp
*/
class Queue {
	protected $_id;
	protected $_data;

	const IDENT = __CLASS__;

	public function __construct($queue) {
		$queue = trim($queue);

		if (empty($queue)) {
			$id = 0;
		} else if (is_numeric($queue)) {
			$id = $queue;
		} else {
			$id = $this->getQueueIdByName($queue);
		}

		if (!is_numeric($id) || $id == 0) {
			throw new Exception('Queue not found!');
		} else {
			$this->_id = $id;
			$config = Ini_Config::getInstance();
			$log = App_Log::getInstance(self::IDENT);
			$db = App_Db::getInstance($config->database->default);

			$sql = $db->select()
				->from('queue')
				->where('queue_id = ?', $id);

			try {
				$log->debug($sql->__toString());
				$stmt = $sql->query();
				$result = $stmt->fetchAll();
				$this->_data = $result[0];
			} catch (Exception $error) {
				throw new Exception($error->getMessage());
			}
		}
	}

	public function __get($key) {
		if (isset($this->_data[$key])) {
			return $this->_data[$key];
		} else {
			return false;
		}
	}

	public function getName() {
		if (isset($this->_data['queue_name'])) {
			return $this->_data['queue_name'];
		} else {
			return null;
		}
	}

	public function getId() {
		if (!isset($this->_id)) {
			return 0;
		} else {
			return $this->_id;
		}
	}

	public static function getQueueIdByName($queue) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance();
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('queue', 'queue_id')
			->where('queue_name = ?', $queue);

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			$result = $stmt->fetchAll();
			if (empty($result)) {
				return 0;
			} else {
				return $result[0]['queue_id'];
			}
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return 0;
		}
	}

	public function getMessages($page = 1, $limit = 15) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('message')
			->where('queue_id = ?', $this->_id)
			->order('created DESC');

		if (!empty($limit)) {
			$sql->limitPage($page, $limit);
		}

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			return $stmt->fetchAll();
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return array();
		}
	}

	public function deleteMessage($messageId) {
		$config = Ini_Config::getInstance();
		$db = App_Db::getInstance($config->database->default);

		$where[] = $db->quoteInto('message_id = ?', $messageId);
		$where[] = $db->quoteInto('queue_id = ?', $this->_id);

		$result = $db->delete('message', $where);
		if ($result > 0) {
			return true;
		} else {
			return false;
		}
	}

	public function flush() {
		$config = Ini_Config::getInstance();
		$db = App_Db::getInstance($config->database->default);

		$where[] = $db->quoteInto('queue_id = ?', $this->_id);

		$result = $db->delete('message', $where);
		if ($result > 0) {
			return true;
		} else {
			return false;
		}
	}
}

?>
