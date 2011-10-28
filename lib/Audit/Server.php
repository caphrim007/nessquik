<?php

/**
* @author Tim Rupp
*/
class Audit_Server {
	protected $_id;
	protected $_data;
	protected $_adapter;

	const IDENT = __CLASS__;

	public function __construct($id) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$this->_id = $id;
		$this->_data = $this->read();

		if (!empty($this->_data['adapter'])) {
			$config = $this->asConfig();
			$adapter = $this->_data['adapter'];
			$class = 'Audit_Server_Adapter_' . $adapter;

			$this->_adapter = new $class($config);
		}
	}

	public function __get($key) {
		switch($key) {
			case 'id':
				return $this->_id;
			case 'adapter':
				return $this->_adapter;
			default:
				break;
		}

		if (isset($this->_data[$key])) {
			return $this->_data[$key];
		} else {
			return false;
		}
	}

	public function __set($key, $val) {
		switch($key) {
			case 'id':
				return false;
		}

		$this->_data[$key] = $val;
	}

	public function read() {
		$config = Ini_Config::getInstance();
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('scanners')
			->where('id = ?', $this->_id);

		$stmt = $sql->query();
		$results = $stmt->fetchAll();
		return $results[0];
	}

	public function delete() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		try {
			$where = $db->quoteInto('id = ?', $this->_id);
			$result = $db->delete('scanners', $where);

			return true;
		} catch (Exception $error) {
			throw new Audit_Server_Exception($error->getMessage());
		}
	}

	public function update() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);
		$filter = new Zend_Filter_Boolean(Zend_Filter_Boolean::ALL);

		$where = $db->quoteInto('id = ?', $this->id);
		$result = $db->update('scanners', $this->_data, $where);

		if ($result > 0) {
			return true;
		} else {
			return false;
		}
	}

	public function asConfig() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		try {
			if (empty($this->_data)) {
				return new Zend_Config(array());
			} else {
				$config = array(
					'id' => $this->_data['id'],
					'name' => $this->_data['name'],
					'description' => $this->_data['description'],
					'adapter' => $this->_data['adapter'],
					'params' => array(
						'host' => $this->_data['host'],
						'port' => $this->_data['port'],
						'username' => $this->_data['username'],
						'password' => $this->_data['password']
					)
				);

				return new Zend_Config($config);
			}
		} catch (Exception $error) {
			$log->err($error->getMessage());
			throw new Audit_Server_Exception($error->getMessage());
		}
	}

	public function countRunning() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()->from('audits_running_on_scanner');

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			return $stmt->rowCount();
		} catch (Exception $error) {
			throw new Audit_Server_Exception($error->getMessage());
		}
	}
}

?>
