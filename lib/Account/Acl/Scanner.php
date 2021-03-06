<?php

/**
* @author Tim Rupp
*/
class Account_Acl_Scanner extends Account_Acl_Abstract {
	const IDENT = __CLASS__;

	public function __construct() {
		$this->_config = Ini_Config::getInstance();
		$this->_log = App_Log::getInstance(self::IDENT);
		$this->_db = App_Db::getInstance($this->_config->database->default);
	}

	/**
	* @throws Account_Acl_Exception
	* @return boolean
	*/
	public function isAllowed($resource) {
		$constraints = array();

		$sql = $db->select()
			->from('acls_scanners', array('scanner_id'))
			->where(sprintf('%s = %s',
				$db->quoteIdentifier('account_id'),
				$db->quote($this->accountId)
			))
			->limit(1);

		if (is_array($resource)) {
			foreach($resource as $res) {
				$constraints[] = sprintf('%s = %s',
					$db->quoteIdentifier('scanner_id'),
					$db->quote($res)
				);
			}

			$tmp = implode(' OR ', $constraints);
			$sql->where($tmp);
		} else {
			$sql->where(sprintf('%s = %s',
				$db->quoteIdentifier('scanner_id'),
				$db->quote($resource)
			));
		}

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			$result = $stmt->fetchAll();

			if (count($result) == 1) {
				return true;
			} else {
				return false;
			}
		} catch (Exception $error) {
			throw new Account_Acl_Exception($error->getMessage());
		}
	}

	public function get() {
		$sql = $db->select()
			->from('acls_scanners', array('scanner_id'))
			->where(sprintf('%s = %s',
				$db->quoteIdentifier('account_id'),
				$db->quote($this->accountId)
			));

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			$result = $stmt->fetchAll();
			return $result;
		} catch (Exception $error) {
			throw new Account_Acl_Exception($error->getMessage());
		}
	}
}

?>
