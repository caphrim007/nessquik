<?php

/**
* @author Tim Rupp
*/
class Token {
	protected $token;

	const IDENT = __CLASS__;

	public function __construct($token) {
		if (!empty($token)) {
			$this->token = $token;
		}
	}

	public function get() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('tokens')
			->where('token = ?', $this->token);

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

	public function getAccountId() {
		$token = $this->get();

		return $token['account_id'];
	}

	public function getProxyId() {
		$token = $this->get();

		return $token['proxy_id'];
	}

	public function expiresSoon() {
		$token = $this->get();

		$date = new Zend_Date;
		$validTo = new Zend_Date($token['valid_to'], Zend_Date::ISO_8601);

		// Check for tokens that will expire in 5 minutes
		$validTo->subSecond('300');

		if ($validTo->compare($date) <= 0) {
			return true;
		} else {
			return false;
		}
	}

	public function isProxy() {
		$token = $this->get();

		if ($token['proxy_id'] > 0) {
			return true;
		} else {
			return false;
		}
	}

	public function getValidity() {
		$token = $this->get();

		if (empty($token)) {
			$validFrom = new Zend_Date(0, Zend_Date::TIMESTAMP);
			$validTo = new Zend_Date(0, Zend_Date::TIMESTAMP);
		} else {
			$validFrom = new Zend_Date($token['valid_from'], Zend_Date::ISO_8601);
			$validTo = new Zend_Date($token['valid_to'], Zend_Date::ISO_8601);
		}

		return array(
			'from' => $validFrom->get(Zend_Date::W3C),
			'to' => $validTo->get(Zend_Date::W3C)
		);
	}
}

?>
