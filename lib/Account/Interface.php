<?php

/**
* @author Tim Rupp
*/
class Account_Interface {
	/**
	* @var integer
	*/
	protected $accountId;

	const IDENT = __CLASS__;

	public function __construct($accountId) {
		if (is_numeric($accountId)) {
			$this->accountId = $accountId;
		} else {
			$this->accountId = 0;
		}
	}

	public function getLimit($limit) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('accounts_interface', array($limit))
			->where('account_id = ?', $this->accountId);

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			$result = $stmt->fetchAll();

			if (count($result) > 0) {
				return $result[0][$limit];
			} else {
				return null;
			}
		} catch (Exception $error) {
			throw new Account_Interface_Exception($error->getMessage());
		}
	}

	public function getLimits() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('accounts_interface')
			->where('account_id = ?', $this->accountId);

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			$result = $stmt->fetchAll();

			if (count($result) > 0) {
				return $result[0];
			} else {
				return array();
			}
		} catch (Exception $error) {
			throw new Account_Interface_Exception($error->getMessage());
		}
	}

	public function updateLimit($limit, $value) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		switch($limit) {
			case 'module':
			case 'controller':
			case 'action':
				return;
			default:
				$data = array($limit => $value);
				break;
		}

		try {
			$where = $db->quoteInto('account_id = ?', $this->accountId);
			$result = $db->update('accounts_interface', $data, $where);
			if ($result == 0) {
				$log->err('Account interface settings were not updated. Perhaps they dont exist. Trying to create them.');
				$data['account_id'] = $this->accountId;
				$result = $db->insert('accounts_interface', $data, $where);
				if ($result == 0) {
					throw new Exception('Failed to insert or update account interface limits!');
				} else {
					return true;
				}
			} else {
				return true;
			}
		} catch (Exception $error) {
			throw new Account_Interface_Exception($error->getMessage());
		}
	}

	public function getDefaults() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('accounts_interface')
			->where('account_id = ?', $this->accountId);

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			$result = $stmt->fetchAll();

			if (count($result) > 0) {
				return $result[0];
			} else {
				return array();
			}
		} catch (Exception $error) {
			throw new Account_Interface_Exception($error->getMessage());
		}
	}

	public function updateDefaults($default, $value) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		switch($default) {
			case 'default_policy_view':
			case 'module':
			case 'controller':
			case 'action':
				return;
			default:
				$data = array($default => $value);
				break;
		}

		try {
			$where = $db->quoteInto('account_id = ?', $this->accountId);
			$result = $db->update('accounts_interface', $data, $where);
			if ($result == 0) {
				$log->err('Account interface settings were not updated. Perhaps they dont exist. Trying to create them.');
				$data['account_id'] = $this->accountId;
				$result = $db->insert('accounts_interface', $data, $where);
				if ($result == 0) {
					throw new Exception('Failed to insert or update account interface defaults!');
				} else {
					return true;
				}
			}
			return true;
		} catch (Exception $error) {
			throw new Account_Interface_Exception($error->getMessage());
		}
	}
}

?>
