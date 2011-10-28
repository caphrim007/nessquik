<?php

/**
* @author Tim Rupp
*/
class Token_Util {
	const IDENT = __CLASS__;

	/**
	* @throws Token_Exception
	*/
	public static function create($account_id, $proxy_id) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$date = new Zend_Date;
		$validTo = null;
		$validFrom = $date->get(Zend_Date::W3C);

		if (isset($config->tokens->timeout)) {
			$validTo = $date->addSecond($config->tokens->timeout);
		} else {
			$validTo = $date->addSecond(86400);
		}

		$validTo = $validTo->get(Zend_Date::W3C);

		$random = mt_rand(0, 255);
		$token = md5($account_id . $random);

		if (empty($_SERVER['REMOTE_ADDR'])) {
			$remote = '127.0.0.1';
		} else {
			$remote = $_SERVER['REMOTE_ADDR'];
		}

		try {
			$data = array(
				'account_id' => $account_id,
				'proxy_id' => $proxy_id,
				'token' => $token,
				'remote_address' => $remote,
				'valid_from' => $validFrom,
				'valid_to' => $validTo
			);

			$db->insert('tokens', $data);
			return $token;
		} catch (Exception $error) {
			throw new Token_Exception($error->getMessage());
		}
	}

	/**
	* @throws Token_Exception
	*/
	public function read($accountId, $proxyId) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$validFrom = new Zend_Date;
		$validTo = clone $validFrom;
		$validTo->addSecond(1);

		if (empty($_SERVER['REMOTE_ADDR'])) {
			$remote = '127.0.0.1';
		} else {
			$remote = $_SERVER['REMOTE_ADDR'];
		}

		$sql = $db->select()
			->from('tokens')
			->where('account_id = ?', $accountId)
			->where('proxy_id = ?', $proxyId)
			->where('remote_address = ?', $remote)
			->where('valid_from = ?', $validFrom->get(Zend_Date::W3C))
			->where('valid_to = ?', $validTo->get(Zend_Date::W3C));

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			$result = $stmt->fetchAll();

			if (empty($result)) {
				return array();
			} else {
				return $result[0];
			}
		} catch (Exception $error) {
			throw new Token_Exception($error->getMessage());
		}
	}
}

?>
