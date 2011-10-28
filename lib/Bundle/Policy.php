<?php

/**
* @author Tim Rupp
*/
class Bundle_Policy {
	protected $_data;

	const IDENT = __CLASS__;

	public function __construct() {
		$this->reset();
	}

	public function name($name) {
		$this->_data['name'] = $name;
	}

	public function owner($username) {
		$this->_data['owner'] = $username;
	}

	public function ownerId($accountId) {
		$this->_data['ownerId'] = $accountId;
	}

	public function reset($part = null) {
		$this->_data = array(
			'name' => '',
			'owner' => '',
			'ownerId' => 0,
			'page' => 0,
			'limit' => 0
		);
	}

	public function limit($limit) {
		if (is_numeric($limit)) {
			$this->_data['limit'] = $limit;
		}
	}

	public function page($page) {
		if (is_numeric($page)) {
			$this->_data['page'] = $page;
		}
	}

	public function __toString() {
		$sql = $this->_prepareQuery();
		return $sql->__toString();
	}

	public function get() {
		$log = App_Log::getInstance(self::IDENT);

		$sql = $this->_prepareQuery();
		$log->debug($sql->__toString());

		$stmt = $sql->query();
		$result = $stmt->fetchAll();

		return $result;
	}

	protected function _prepareQuery() {
		$config = Ini_Config::getInstance();
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('accounts_policies')
			->order('policy_name ASC');

		if ($this->_data['ownerId'] != 0) {
			$sql->where('account_id = ?', $this->_data['ownerId']);
		} else {
			if (!empty($this->_data['owner'])) {
				$sql->where('username = ?', $owner);
			}
		}

		if (!empty($this->_data['name'])) {
			$name = $this->_data['name'];
			$name = '%' . substr($name, 1) . '%';
			$sql->where('policy_name ILIKE ?', $name);
		}

		if (!empty($this->_data['limit'])) {
			$sql->limitPage($this->_data['page'], $this->_data['limit']);
		}

		return $sql;
	}

	public function count($limit = true) {
		$sql = $this->_prepareQuery();

		if ($limit === false) {
			$sql->limit(0);
		}

		$stmt = $sql->query();
		$result = $stmt->fetchAll();
		return count($result);
	}
}

?>
