<?php

/**
* @author Tim Rupp
*/
class Account_Settings {
	/**
	* @var integer
	*/
	protected $accountId;

	protected $_data;
	protected $_config;
	protected $_log;
	protected $_db;

	const IDENT = __CLASS__;

	public function __construct($accountId) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		if (is_numeric($accountId)) {
			$this->accountId = $accountId;
		} else {
			$this->accountId = 0;
		}

		$this->_config = $config;
		$this->_log = $log;
		$this->_db = $db;
		$this->_data = $this->read();
	}

	public function __set($key, $val) {
		$this->_data[$key] = $val;
	}

	public function __get($key) {
		if (isset($this->_data[$key])) {
			return $this->_data[$key];
		} else {
			return false;
		}
	}

	public function __isset($key) {
		if (isset($this->_data[$key])) {
			return true;
		} else {
			return false;
		}
	}

	public function read() {
		$data = array();

		$sql = $this->_db->select()
			->from('accounts_settings')
			->where('account_id = ?', $this->accountId);

		$this->_log->debug($sql->__toString());
		$stmt = $sql->query();
		$results= $stmt->fetchAll();

		foreach($results as $result) {
			$attr = $result['attribute'];
			$data[$attr] = $result['value'];
		}

		return $data;
	}

	public function update() {
		$this->_db->beginTransaction();

		try {
			foreach($this->_data as $key => $value) {
				$where = array();
				$where[] = $this->_db->quoteInto('account_id = ?', $this->accountId);
				$where[] = $this->_db->quoteInto('attribute = ?', $key);

				$data = array(
					'value' => $value
				);

				$result = $this->_db->update('accounts_settings', $data, $where);
				if ($result == 0) {
					// Setting may not exist, so create it
					$data = array(
						'account_id' => $this->accountId,
						'attribute' => $key,
						'value' => $value
					);
					$result = $this->_db->insert('accounts_settings', $data);
				}
			}

			$this->_db->commit();
			return true;
		} catch (Exception $error) {
			$this->_log->err($error->getMessage());
			$this->_db->rollBack();
			return false;
		}
	}
}

?>
