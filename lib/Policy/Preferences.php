<?php

/**
* @author Tim Rupp
*/
class Policy_Preferences {
	const IDENT = __CLASS__;

	protected $_id;
	protected $_data;

	public function __construct($id) {
		$this->_id = $id;
		$this->_data = $this->read();
	}

	public function __get($key) {
		switch($key) {
			case 'id':
				return $this->_id;
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
		$this->_data[$key] = $val;
	}

	public function get($key) {
		return $this->__get($key);
	}

	public function clear() {
		$config = Ini_Config::getInstance();
		$db = App_Db::getInstance($config->database->default);

		$where = $db->quoteInto('policy_id = ?', $this->_id);
		$db->delete('policies_preferences', $where);
		$this->_data = array();
	}

	public function create($attribute, $value) {
		$config = Ini_Config::getInstance();
		$db = App_Db::getInstance($config->database->default);

		$data = array(
			'policy_id' => $this->_id,
			'attribute' => $attribute,
			'value' => $value
		);

		$db->insert('policies_preferences', $data);
	}

	public function read() {
		$config = Ini_Config::getInstance();
		$db = App_Db::getInstance($config->database->default);

		$return = array();

		$sql = $db->select()
			->from('policies_preferences')
			->where('policy_id = ?', $this->_id);

		$stmt = $sql->query();
		$results = $stmt->fetchAll();

		foreach($results as $result) {
			$attribute = $result['attribute'];
			$value = $result['value'];
			$return[$attribute] = $value;
		}

		return $return;
	}

	public function update() {
		$config = Ini_Config::getInstance();
		$db = App_Db::getInstance($config->database->default);
		$log = App_Log::getInstance(self::IDENT);

		try {
			$db->beginTransaction();

			$where = $db->quoteInto('policy_id = ?', $this->_id);
			$db->delete('policies_preferences', $where);
		
			foreach($this->_data as $attribute => $value) {
				$this->create($attribute, $value);
			}

			$db->commit();
		} catch (Exception $error) {
			$db->rollback();
		}
	}

	public function asKv() {
		$config = Ini_Config::getInstance();
		$db = App_Db::getInstance($config->database->default);
		$log = App_Log::getInstance(self::IDENT);

		$sql = $db->select()
			->from('policies_preferences', array('preference', 'value'))
			->joinLeft('plugin_preferences', 'policies_preferences.attribute = plugin_preferences.id')
			->where('policy_id = ?', $this->_id);

		$stmt = $sql->query();
		$results = $stmt->fetchAll();
		return $results;
	}
}

?>
