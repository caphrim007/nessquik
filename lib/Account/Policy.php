<?php

/**
* @author Tim Rupp
*/
class Account_Policy {
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

	public function hasPolicies() {
		$policies = $this->getPolicies(1,1);

		if (count($policies) > 0) {
			return true;
		} else {
			return false;
		}
	}

	/**
	* @throws Account_Exception
	*/
	public function getPolicies($page = 1, $limit = 15) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('accounts_policies')
			->where(sprintf('%s = %s',
				$db->quoteIdentifier('account_id'),
				$db->quote($this->accountId)
			))
			->order('policy_name ASC');

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

	public function hasPolicy($policyId) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('accounts_policies')
			->where(sprintf('%s = %s',
				$db->quoteIdentifier('account_id'),
				$db->quote($this->accountId)
			))
			->where(sprintf('%s = %s',
				$db->quoteIdentifier('policy_id'),
				$db->quote($policyId)
			))
			->limit(1);

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			$result = $stmt->fetchAll();

			if (count($result) == 0) {
				return false;
			} else {
				return true;
			}
		} catch (Exception $error) {
			throw new Account_Exception($error->getMessage());
		}
	}

	public function delete($policyId) {
		$policy = new Policy($policyId);

		try {
			$result = $policy->delete();
			return $result;
		} catch (Policy_Exception $error) {
			throw new Account_Exception($error->getMessage());
		}
	}

	public function count() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('accounts_policies')
			->where(sprintf('%s = %s',
				$db->quoteIdentifier('account_id'),
				$db->quote($this->accountId)
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
