<?php

/**
* @author Tim Rupp
*/
class Account_Scanner {
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
	* @throws Account_Exception
	*/
	public function getScanners($page = 1, $limit = 15) {
		$scanners = array();
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('accounts_scanners')
			->where(sprintf('%s = %s',
				$db->quoteIdentifier('account_id'),
				$db->quote($this->accountId)
			))
			->order('scanner_name ASC');

		if (!empty($limit)) {
			$sql->limitPage($page, $limit);
		}

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			$results = $stmt->fetchAll();

			if (empty($results)) {
				return array();
			} else {
				foreach($results as $scanner) {
					$id = $scanner['account_id'];
					$scanners[$id] = $scanner;
				}

				return array_values($scanners);
			}
		} catch (Exception $error) {
			throw new Account_Exception($error->getMessage());
		}
	}

	public function hasAccount($server) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);
		$account = new Account($this->accountId);

		if ($server instanceof Audit_Server) {
			$log->debug('Provided scanner was an instance of Audit_Server');
		} else {
			$log->debug('Provided scanner may have been an audit server ID. Attempting to instantiate');
			$server = new Audit_Server($server);
		}

		$serverLog = App_Log::getInstance(get_class($server));
		$server->adapter->setLogger($serverLog);
		$server->adapter->setLogin($server->adapter->getUsername(), $server->adapter->getPassword());

		$hasUser = $server->adapter->hasUser($account->username);
		if ($hasUser === false) {
			$log->debug('Server does not have the users account');
			return false;
		} else {
			$log->debug('Server indeed does have the users account');
			return true;
		}
	}
}

?>
