<?php

/**
* API wrapper around the Authorize class
*
* This class wraps other classes that provide the
* functionality exposed through the authorize namespace
*
* @author Tim Rupp
*/
class Api_Authorize {
	const IDENT = __CLASS__;

	/**
	* @param string $username Username to authenticate as
	* @param string $password Password for username
	* @param string $proxy_account Account to masquerade as
	* @return string
	*/
	public function getToken($username, $password, $proxy_account = null) {
		$allowed = false;

		$ini = Ini_Authentication::getInstance();
		$log = App_Log::getInstance(self::IDENT);

		try {
			$accountId = Account_Util::getId($username);
			if ($accountId == 0) {
				throw new Api_Exception(sprintf('No account could be found for the provided username %s', $username));
			} else {
				$account = new Account($accountId);
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			$adapter = new App_Auth_Adapter_Multiple($ini, $username, $password);
			$result = $adapter->authenticate();

			$messages = $result->getMessages();
			foreach($messages as $key => $message) {
				if (empty($message)) {
					continue;
				} else {
					$log->debug($message);
				}
			}

			if ($result->isValid()) {
				$log->debug('Successfully authenticated; returning a token');

				$account_id = Account_Util::getId($username);
				$proxy_id = Account_Util::getId($proxy_account);

				if ($account_id == 0) {
					throw new Token_Exception('Account did not resolve to a known ID');
				}

				if ($proxy_id == 0 && !is_null($proxy_account)) {
					/**
					* Proxy IDs cannot be zero since that is the return value of
					* the getId method. If no proxy was specified though, we _expect_
					* zero to be returned, so keep the above condition an AND
					*/
					throw new Token_Exception('Proxy account did not resolve to a known ID');
				}

				$token = Token_Util::read($account_id, $proxy_id);
				if (empty($token)) {
					$token = Token_Util::create($account_id, $proxy_id);
				}

				return $token;
			} else {
				throw new Api_Exception('The username or password you entered was incorrect');
			}
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return null;
		}
	}

	/**
	* @param string $token Access token
	* @return array
	*/
	public function getValidity($token) {
		$log = App_Log::getInstance(self::IDENT);

		try {
			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			$token = new Token($token);
			return $token->getValidity();
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return array();
		}
	}
}

?>
