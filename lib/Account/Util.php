<?php

/**
* @author Tim Rupp
*/
class Account_Util {
	const IDENT = __CLASS__;

	public static function create($name) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);
		$defaults = Ini_AccountDefaults::getInstance();
		$date = new Zend_Date;

		$name = trim($name);

		try {
			$data = array(
				'username' => $name
			);
			$result = $db->insert('accounts', $data);

			if ($result > 0) {
				$log->debug('Created new account in nessquik');
			}

			$accountId = $db->lastSequenceId('accounts_id_seq');

			// Set any default roles that may be specified in
			// the default account settings
			//if (isset($defaults->roles)) {
			//	$roles = $defaults->roles->toArray();
			//	$account = new Account($accountId);

			//	foreach($roles as $roleId => $roleName) {
			//		$account->role->addRole($roleId);
			//	}
			//}

			return $accountId;
		} catch (Exception $error) {
			throw new Account_Util_Exception($error->getMessage());
		}
	}

	/**
	* @throws Account_Util_Exception
	*/
	public static function getId($account) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		/**
		* This check is required because when doing authentication
		* through the URL (ie username=joe&password=blow) this code
		* is called by the Account Login controller.
		*
		* The controller will pass the sessionUser value which will
		* be empty when doing this URL authentication. When that
		* value (the username) is empty, the SQL statement that is
		* created further down will not bind an empty parameter to
		* the '?' placeholder and you'll get an exception when
		* trying to run the query.
		*/
		if (empty($account)) {
			return 0;
		}

		try {
			$sql = $db->select()
				->from('accounts_ids')
				->where('acctname = ?', $account)
				->orWhere('mapname = ?', $account);

			$log->debug($sql->__toString());

			$stmt = $sql->query();
			$result = $stmt->fetch();

			if (empty($result)) {
				return 0;
			} else {
				return $result['id'];
			}
		} catch (Exception $error) {
			throw new Account_Util_Exception($error->getMessage());
		}
	}

	/**
	* @throws Account_Util_Exception
	*/
	public static function getAccounts($page = 1, $limit = 15) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('accounts')
			->order('username ASC');

		if (!empty($limit)) {
			$sql->limitPage($page, $limit);
		}

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			return $stmt->fetchAll();
		} catch (Exception $error) {
			throw new Account_Util_Exception($error->getMessage());
		}
	}

	public static function exists($account) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('accounts')
			->limit(1);

		if (is_numeric($account)) {
			$sql->where('id = ?', $account);
		} else {
			$sql->where('username = ?', $account);
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
			$log->err($error->getMessage());
			return false;
		}
	}
}

?>
