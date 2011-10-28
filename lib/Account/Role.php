<?php

/**
* @author Tim Rupp
*/
class Account_Role {
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

	/**
	* @throws Account_Role_Exception
	*/
	public function addRole($roleId) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		if (!is_numeric($roleId)) {
			throw new Account_Role_Exception('The specified role ID was not a number');
		}

		if ($this->hasRole($roleId)) {
			$log->debug(sprintf('Account already has a role with ID "%s"assigned to them', $roleId));
			return true;
		}

		try {
			$data = array(
				'account_id' => $this->accountId,
				'role_id' => $roleId
			);

			$result = $db->insert('accounts_roles', $data);

			return true;
		} catch (Exception $error) {
			throw new Account_Role_Exception($error->getMessage());
		}
	}

	/**
	* @throws Account_Role_Exception
	*/
	public function removeRole($roleId) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		try {
			$where[] = $db->quoteInto('account_id = ?', $this->accountId);
			$where[] = $db->quoteInto('role_id = ?', $roleId);

			$result = $db->delete('accounts_roles', $where);
			return true;
		} catch (Exception $error) {
			throw new Account_Role_Exception($error->getMessage());
		}
	}

	/**
	* @throws Account_Role_Exception
	*/
	public function clear() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		try {
			$where = $db->quoteInto('account_id = ?', $this->accountId);
			$result = $db->delete('accounts_roles', $where);

			return true;
		} catch (Exception $error) {
			throw new Account_Role_Exception($error->getMessage());
		}
	}

	/**
	* @throws Account_Role_Exception
	*/
	public function hasRole($role) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('accounts_roles', array('id'))
			->joinLeft('roles',
				sprintf('%s.%s = %s.%s',
					$db->quoteIdentifier('accounts_roles'),
					$db->quoteIdentifier('role_id'),
					$db->quoteIdentifier('roles'),
					$db->quoteIdentifier('id')
				),
				null
			)
			->where(sprintf('%s.%s = %s',
				$db->quoteIdentifier('accounts_roles'),
				$db->quoteIdentifier('account_id'),
				$db->quote($this->accountId)
			))
			->limit(1);

		if (is_numeric($role)) {
			$sql->where(sprintf('%s.%s = %s',
					$db->quoteIdentifier('roles'),
					$db->quoteIdentifier('id'),
					$db->quote($role))
				)
				->orWhere(sprintf('%s.%s = %s',
					$db->quoteIdentifier('roles'),
					$db->quoteIdentifier('name'),
					$db->quote((string)$role))
				);
		} else {
			$sql->where(sprintf('%s.%s = %s',
					$db->quoteIdentifier('roles'),
					$db->quoteIdentifier('name'),
					$db->quote($role))
				);
		}

		try {
			$log->debug($sql->__toString());

			$stmt = $sql->query();
			$result = $stmt->fetchAll();

			if (count($result) > 0) {
				return true;
			} else {
				return false;
			}
		} catch (Exception $error) {
			throw new Account_Role_Exception($error->getMessage());
		}
	}
}

?>
