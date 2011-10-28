<?php

/**
* @author Tim Rupp
*/
class Regex {
	protected $_id;
	protected $_data;
	protected $_regex;

	const IDENT = __CLASS__;

	public function __construct($id) {
		if (is_numeric($id)) {
			$this->_id = $id;

			$config = Ini_Config::getInstance();
			$log = App_Log::getInstance(self::IDENT);
			$db = App_Db::getInstance($config->database->default);

			$sql = $db->select()
				->from('regex')
				->where('id = ?', $id);

			try {
				$log->debug($sql->__toString());
				$stmt = $sql->query();
				$result = $stmt->fetchAll();
				$this->_data = $result[0];
			} catch (Exception $error) {
				throw new Exception($error->getMessage());
			}
		} else {
			throw new Exception('The specified ID is not a number');
		}
	}

	public function __set($key, $val) {
		switch($key) {
			case 'id':
				return;
		}

		$this->_data[$key] = $val;
	}

	public function __get($key) {
		if (isset($this->_data[$key])) {
			return $this->_data[$key];
		} else {
			return false;
		}
	}

	public function update($data = array()) {
		$config = Ini_Config::getInstance();
		$db = App_Db::getInstance($config->database->default);
		$table = new Table_Regex($db);

		if (!empty($data)) {
			foreach($data as $key => $val) {
				$this->$key = $val;
			}
		}

		$where = $table->getAdapter()->quoteInto('id = ?', $this->_id);
		return $table->update($this->_data, $where);
	}

	public function delete() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		try {
			$table = new Table_Regex($db);
			$where = $table->getAdapter()->quoteInto('id = ?', $this->_id);

			$log->debug(sprintf('Removing database entry for URL with ID "%s"', $this->_id));

			$result = $table->delete($where);

			return true;
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @throws Regex_Exception
	*/
	public function get($type, $page = 1, $limit = 15) {
		if (isset($this->_regex[$type])) {
			$regex = $this->_regex[$type];
		} else {
			$class = 'Regex_'.$type;
			$regex = new $class($this->_id);
		}

		if ($regex instanceof Regex_Abstract) {
			return $regex->get($page, $limit);
		} else {
			throw new Regex_Exception('The supplied regex type is invalid');
		}
	}

	/**
	* @throws Regex_Exception
	*/
	public function getIds($type, $page = 1, $limit = 15) {
		if (isset($this->_regex[$type])) {
			$regex = $this->_regex[$type];
		} else {
			$class = 'Regex_'.$type;
			$regex = new $class($this->_id);
		}

		if ($regex instanceof Regex_Abstract) {
			return $regex->getIds($page, $limit);
		} else {
			throw new Regex_Exception('The supplied regex type is invalid');
		}
	}

	public function addAutomation($automation) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		if (!is_numeric($this->_id)) {
			throw new Regex_Exception('The supplied Regex ID is invalid');
		} else if (empty($automation)) {
			throw new Regex_Exception('The supplied automation was empty');
		} else if (!is_numeric($automation)) {
			throw new Regex_Exception('The supplied automation was not a numeric value');
		}

		try {
			$data = array(
				'regex_id' => $this->_id,
				'automation_id' => $automation
			);
			$db->insert('regex_automations', $data);
			return true;
		} catch (Exception $error) {
			throw new Regex_Exception($error->getMessage());
		}
	}

	public function deleteAutomation($automation) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		if (!is_numeric($this->_id)) {
			throw new Regex_Exception('The supplied Regex ID is invalid');
		}

		if (empty($automation)) {
			throw new Regex_Exception('The supplied automation was empty');
		}

		try {
			$where[] = $db->quoteInto('regex_id = ?', $this->_id);
			$where[] = $db->quoteInto('automation_id = ?', $automation);

			return $db->delete('regex_automations', $where);
		} catch (Exception $error) {
			throw new Regex_Exception($error->getMessage());
		}
	}

	public function clearAutomations() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		if (!is_numeric($this->_id)) {
			throw new Regex_Exception('The supplied Regex ID is invalid');
		}

		try {
			$where = $db->quoteInto('regex_id = ?', $this->_id);
			return $db->delete('regex_automations', $where);
		} catch (Exception $error) {
			throw new Regex_Exception($error->getMessage());
		}
	}
}

?>
