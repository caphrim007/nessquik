<?php

/**
* @author Tim Rupp
*/
class Account_Audit_Status_Parked {
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

	public function get($page = 1, $limit = 15) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('accounts_roles', null)
			->join('roles_permissions',
				sprintf('%s.%s = %s.%s', 
					$db->quoteIdentifier('accounts_roles'),
					$db->quoteIdentifier('role_id'),
					$db->quoteIdentifier('roles_permissions'),
					$db->quoteIdentifier('role_id')
				),
				null
			)
			->join('permissions_audit',
				sprintf('%s.%s = %s.%s', 
					$db->quoteIdentifier('roles_permissions'),
					$db->quoteIdentifier('permission_id'),
					$db->quoteIdentifier('permissions_audit'),
					$db->quoteIdentifier('id')
				),
				null
			)
			->join('audits',
				sprintf('%s.%s = %s.%s',
					$db->quoteIdentifier('permissions_audit'),
					$db->quoteIdentifier('resource'),
					$db->quoteIdentifier('audits'),
					$db->quoteIdentifier('id')
				)
			)
			->where(sprintf('%s.%s = %s',
				$db->quoteIdentifier('accounts_roles'),
				$db->quoteIdentifier('account_id'),
				$db->quote($this->accountId)
			))
			->where(sprintf('%s.%s = %s',
				$db->quoteIdentifier('audits'),
				$db->quoteIdentifier('status'),
				$db->quote('N')
			))
			->order('date_scheduled ASC');

		if (!empty($limit)) {
			$sql->limitPage($page, $limit);
		}

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			return $stmt->fetchAll();
		} catch (Exception $error) {
			throw new Account_Exception($error->getMessage());
		}
	}

	public function count() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('accounts_roles', null)
			->join('roles_permissions',
				sprintf('%s.%s = %s.%s', 
					$db->quoteIdentifier('accounts_roles'),
					$db->quoteIdentifier('role_id'),
					$db->quoteIdentifier('roles_permissions'),
					$db->quoteIdentifier('role_id')
				),
				null
			)
			->join('permissions_audit',
				sprintf('%s.%s = %s.%s', 
					$db->quoteIdentifier('roles_permissions'),
					$db->quoteIdentifier('permission_id'),
					$db->quoteIdentifier('permissions_audit'),
					$db->quoteIdentifier('id')
				),
				null
			)
			->join('audits',
				sprintf('%s.%s = %s.%s',
					$db->quoteIdentifier('permissions_audit'),
					$db->quoteIdentifier('resource'),
					$db->quoteIdentifier('audits'),
					$db->quoteIdentifier('id')
				)
			)
			->where(sprintf('%s.%s = %s',
				$db->quoteIdentifier('accounts_roles'),
				$db->quoteIdentifier('account_id'),
				$db->quote($this->accountId)
			))
			->where(sprintf('%s.%s = %s',
				$db->quoteIdentifier('audits'),
				$db->quoteIdentifier('status'),
				$db->quote('N')
			));

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			return $stmt->rowCount();
		} catch (Exception $error) {
			throw new Account_Exception($error->getMessage());
		}
	}
}

?>
