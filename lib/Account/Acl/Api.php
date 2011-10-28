<?php

/**
* @author Tim Rupp
*/
class Account_Acl_Api extends Account_Acl_Abstract {
	const IDENT = __CLASS__;

	public function __construct($accountId) {
		$this->_config = Ini_Config::getInstance();
		$this->_log = App_Log::getInstance(self::IDENT);
		$this->_db = App_Db::getInstance($this->_config->database->default);
		$this->accountId = $accountId;
	}

	/**
	* @throws Account_Acl_Exception
	* @return boolean
	*/
	public function isAllowed($resource) {
		$constraints = array();

		$sql = $this->_db->select()
			->from('acls_api', array('resource'))
			->where(sprintf('%s = %s',
				$this->_db->quoteIdentifier('account_id'),
				$this->_db->quote($this->accountId)
			))
			->limit(1);

		if (is_array($resource)) {
			foreach($resource as $res) {
				if (substr($res, -1) == '*') {
					$res = substr($res, 0, -1);
					$constraints[] = sprintf('%s LIKE %s',
						$this->_db->quoteIdentifier('resource'),
						$this->_db->quote($res.'%')
					);
				}

				$constraints[] = sprintf('%s = %s',
					$this->_db->quoteIdentifier('resource'),
					$this->_db->quote($res)
				);
			}

			$tmp = implode(' OR ', $constraints);
			$sql->where($tmp);
		} else {
			if (substr($resource, -1) == '*') {
				$resource = substr($resource, 0, -1);
				$constraints[] = sprintf('%s LIKE %s',
					$this->_db->quoteIdentifier('resource'),
					$this->_db->quote($resource.'%')
				);
			}

			$constraints[] = sprintf('%s = %s',
				$this->_db->quoteIdentifier('resource'),
				$this->_db->quote($resource)
			);

			$tmp = implode(' OR ', $constraints);
			$sql->where($tmp);
		}

		try {
			$this->_log->debug($sql->__toString());
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
		$sql = $this->_db->select()
			->from('acls_api')
			->where(sprintf('%s = %s',
				$this->_db->quoteIdentifier('account_id'),
				$this->_db->quote($this->accountId)
			));

		try {
			$this->_log->debug($sql->__toString());
			$stmt = $sql->query();
			$result = $stmt->fetchAll();
			return $result;
		} catch (Exception $error) {
			throw new Account_Acl_Exception($error->getMessage());
		}
	}
}

?>
