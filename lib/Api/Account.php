<?php

/**
* API wrapper around the Account class
*
* This class wraps other classes that provide the
* functionality exposed through the account namespace
*
* @author Tim Rupp
*/
class Api_Account {
	const IDENT = __CLASS__;

	/**
	* @param string $token Access token
	* @param string|integer $account_id ID of the account to add a role to
	* @param string|integer $role_id ID of the role to add to the account
	* @return boolean
	*/
	public function addRole($token, $account_id, $role_id) {
		$log = App_Log::getInstance(self::IDENT);

		try {
			return Account::addRole($account_id, $role_id);
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @param string $token Access token
	* @return string
	*/
	public function create($token) {
		$log = App_Log::getInstance(self::IDENT);

		try {
			return Account::create();
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return null;
		}
	}

	/**
	* @param string $token Access token
	* @param string|integer $account_id ID of the account to delete
	* @return boolean
	*/
	public function delete($token, $account_id) {
		$log = App_Log::getInstance(self::IDENT);

		try {
			return Account::delete($account_id);
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @param string $token Access token
	* @param string|integer $account_id ID of the account to remove a role from
	* @param string|integer $role_id ID of the role to remove from the account
	* @return boolean
	*/
	public function removeRole($token, $account_id, $role_id) {
		$log = App_Log::getInstance(self::IDENT);

		try {
			return Account::removeRole($account_id, $role_id);
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @param string $token Access token
	* @param string|integer $account_id ID of the account to change the password of
	* @param string $old_password Old password
	* @param string $new_passowrd New password
	* @return boolean
	*/
	public function setPassword($token, $account_id, $old_password, $new_password) {
		$log = App_Log::getInstance(self::IDENT);

		try {
			return Account::setPassword($account_id, $old_password, $new_password);
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @param string $token Access token
	* @param string|integer $account_id Account ID to set a role on
	* @param string|array|struct|integer $role Role ID or list of role IDs to set on the account
	* @return boolean
	*/
	public function setRole($token, $account_id, $role) {
		$log = App_Log::getInstance(self::IDENT);

		try {
			return Account::setRole($account_id, $role);
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @param string $token Access token
	* @param string|integer $account_id Account ID to change the username of
	* @param string $username New username for the account
	* @return boolean
	*/
	public function setUsername($token, $account_id, $username) {
		$log = App_Log::getInstance(self::IDENT);

		try {
			return Account::setUsername($account_id, $username);
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @param string $token Access token
	* @param string $policyId ID of the policy to set as the default
	* @return boolean
	*/
	public function setDefault($token, $policyId) {
		$log = App_Log::getInstance(self::IDENT);

		$token = new Token($token);
		if ($token->isProxy()) {
			$accountId = $token->getProxyId();
		} else {
			$accountId = $token->getAccountId();
		}

		try {
			$account = new Account($accountId);

			if ($account->policy->hasPolicy($policyId)) {
				$status = $account->policy->setDefault($policyId);
				return true;
			} else {
				$log->err('You do not have permission to use this policy');
				return false;
			}
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @param string $token Access token
	* @param string|integer $account_id Account ID to stage for updating
	* @return string
	*/
	public function update($token, $account_id) {
		$log = App_Log::getInstance(self::IDENT);

		try {
			return Account::update($account_id);
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return null;
		}
	}
}

?>
