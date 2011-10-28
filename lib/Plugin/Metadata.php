<?php

/**
* @author Tim Rupp
*/
class Plugin_Metadata {
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
		if (is_array($val)) {
			$val = implode(',', $val);
		}

		$this->_data[$key] = $val;
	}

	public function getData() {
		return $this->_data;
	}

	public function create($attribute, $value) {
		$config = Ini_Config::getInstance();
		$db = App_Db::getInstance($config->database->default);

		$data = array(
			'plugin_id' => $this->_id,
			'attribute' => $attribute,
			'value' => $value
		);

		$db->insert('plugins_metadata', $data);
	}

	public function read() {
		$config = Ini_Config::getInstance();
		$db = App_Db::getInstance($config->database->default);

		$return = array();

		$sql = $db->select()
			->from('plugins_metadata')
			->where('plugin_id = ?', $this->_id);

		$stmt = $sql->query();
		$results = $stmt->fetchAll();

		foreach($results as $result) {
			$attribute = $result['attribute'];
			$value = $result['value'];
			$return[$attribute] = $value;
		}

		return $return;
	}

	public function updateOne($attribute, $value = null) {
		$config = Ini_Config::getInstance();
		$db = App_Db::getInstance($config->database->default);

		$where[] = $db->quoteInto('plugin_id = ?', $this->_id);
		$where[] = $db->quoteInto('attribute = ?', $attribute);

		$data = array(
			'value' => $value
		);

		$result = $db->update('plugins_metadata', $data, $where);
	}

	public function update() {
		$config = Ini_Config::getInstance();
		$db = App_Db::getInstance($config->database->default);

		try {
			$db->beginTransaction();

			foreach($this->_data as $attribute => $value) {
				$data = array(
					'attribute' => $attribute,
					'value' => $value
				);

				$where = $db->quoteInto('plugin_id = ?', $this->_id);
				$db->update('plugins_metadata', $data, $where);
			}

			$db->commit();
		} catch (Exception $error) {
			$db->rollback();
		}
	}

	public function delete($attribute = null) {
		$config = Ini_Config::getInstance();
		$db = App_Db::getInstance($config->database->default);

		$where[] = $db->quoteInto('plugin_id = ?', $this->_id);

		if (!empty($attribute)) {
			$where[] = $db->quoteInto('attribute = ?', $attribute);
		}

		$result = $db->delete('plugins_metadata', $where);
	}
}

?>
