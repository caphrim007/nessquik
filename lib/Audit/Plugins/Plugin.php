<?php

/**
* @author Tim Rupp
*/
class Audit_Plugins_Plugin {
	protected $_data;
	protected $_table;

	const IDENT = __CLASS__;
	
	public function __construct($id) {
		if (empty($id)) {
			throw new Audit_Plugins_Exception('The specified plugin ID cannot be empty');
		}

		$config = Ini_Config::getInstance();
		$db = App_Db::getInstance($config->database->default);

		$table = new Table_Plugins(array('db' => $db));

		$this->_table = $table;
		$this->_data['id'] = $id;
	}

	public function __get($key) {
		if (isset($this->_data[$key])) {
			return $this->_data[$key];
		} else {
			$info = $this->read();
			@$this->_data[$key] = $info[$key];
			return $this->_data[$key];
		}
	}

	public function __set($key, $value) {
		if ($key == 'id') {
			return false;
		} else {
			$this->_data[$key] = $value;
		}
	}

	public function create() {
		$log = App_Log::getInstance(self::IDENT);

		try {
			$this->_table->insert($this->_data);
			return true;
		} catch (Exception $error) {
			if (strlen($this->bugtraq_id) >= 255) {
				$log->debug(sprintf('Freakin super long field found for plugin with ID %s', $this->id));
			}

			$log->err($error->getMessage());
			return false;
		}
	}

	public function read() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->plugin);

		$sql = $db->select()
			->from('plugins')
			->where('id = ?', $this->id)
			->limit(1);

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			$result = $stmt->fetchAll();

			if (empty($result)) {
				return array();
			} else {
				return $result[0];
			}
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	public function update() {
		$log = App_Log::getInstance(self::IDENT);

		try {
			$where = $this->_table->getAdapter()->quoteInto('id = ?', $this->id);
			$this->_table->update($this->_data, $where);
			return true;
		} catch (Exception $error) {
			if (strlen($this->bugtraq_id) >= 255) {
				$log->debug(sprintf('Freakin super long field found for plugin with ID %s', $this->id));
			}

			$log->err($error->getMessage());
			return false;
		}
	}

	public function delete() {
		try {
			$where = $this->_table->getAdapter()->quoteInto('id = ?', $this->id);
			$this->_table->delete($where);
			$this->_data = array();
			return true;
		} catch (Exception $error) {
			throw new Audit_Plugins_Exception($error->getMessage());
		}
	}

	public static function exists($pluginId) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->plugin);

		if (!is_numeric($pluginId)) {
			throw new Audit_Plugins_Exception('The supplied plugin ID is not a numeric value');
		}

		try {
			$sql = $db->select()
				->from('plugins')
				->where('id = ?', $pluginId);

			$log->debug($sql->__toString());
			$stmt = $sql->query();
			$result = $stmt->fetchAll();

			if (empty($result)) {
				return false;
			} else {
				return true;
			}
		} catch (Exception $error) {
			throw new Audit_Plugins_Exception($error->getMessage());
		}
	}
}

?>
