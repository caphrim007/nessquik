<?php

/**
* @author Tim Rupp
*/
class Plugin {
	const IDENT = __CLASS__;

	public $metadata;

	protected $_id;
	protected $_data;

	public function __construct($id) {
		$this->_id = $id;
		$this->_data = $this->read();

		$this->metadata = new Plugin_Metadata($id);
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
		$allowed = array('name','family','script','hash');
		if ($key == 'id') {
			return;
		}

		if (in_array($key, $allowed)) {
			$this->_data[$key] = $val;
		} else {
			$this->metadata->$key = $val;
		}
	}

	public function read() {
		$config = Ini_Config::getInstance();
		$db = App_Db::getInstance($config->database->default);

		$return = array();

		$sql = $db->select()
			->from('plugins')
			->where('id = ?', $this->_id);

		$stmt = $sql->query();
		$results = $stmt->fetchAll();

		if (count($results) > 0) {
			$finding = $results[0];
			$return = array(
				'name' => $finding['name'],
				'family' => $finding['family'],
				'script' => $finding['script'],
				'hash' => $finding['hash']
			);
			return $return;
		} else {
			return array();
		}
	}

	public function update() {
		$config = Ini_Config::getInstance();
		$db = App_Db::getInstance($config->database->default);
		$where = $db->quoteInto('id = ?', $this->_id);
		$result = $db->update('plugins', $this->_data, $where);
		return $result;
	}

	public function delete() {
		$config = Ini_Config::getInstance();
		$db = App_Db::getInstance($config->database->default);
		$where = $db->quoteInto('id = ?', $this->_id);
		$result = $db->delete('plugins', $where);
		return $result;
	}

	public function create() {
		$config = Ini_Config::getInstance();
		$db = App_Db::getInstance($config->database->default);

		$data = $this->_data;
		$data['id'] = $this->_id;

		$result = $db->insert('plugins', $data);
		return $result;
	}
}

?>
