<?php

/**
* @author Tim Rupp
*/
class App_Auth_Adapter_Ldap extends Zend_Auth_Adapter_Ldap {
	const IDENT = __CLASS__;

	/**
	* Authenticate the user
	*
	* @throws Zend_Auth_Adapter_Exception
	* @return Zend_Auth_Result
	*/
	public function authenticate() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$result = parent::authenticate();
		$messages = $result->getMessages();

		$username = $this->getUsername();
		$password = $this->getPassword();
		$acctObject = $this->getAccountObject();

		if ($result->isValid()) {
			$accountId = Account_Util::getId($username);

			if ($accountId == 0) {
				$log->debug(sprintf('Account "%s" does not exist in the database; creating it', $username));
				$accountId = Account_Util::create($username);

				$account = new Account($accountId);
				$random = Zend_OpenId::randomBytes(32);
				$account->setPassword(md5($random));

				$log->debug('Creating new role for account based off of account name');
				$roleId = Role_Util::create($account->username, 'Default account role');
				$account->role->addRole($roleId);
				$account->setPrimaryRole($roleId);

				if (isset($acctObject['userprincipalname'])) {
					$result = $account->createAccountMapping($acctObject['userprincipalname']);
				}

				$log->debug(sprintf('Authentication successful for subject "%s"', $username));
				return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $username, $messages);
			} else {
				return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $username, $messages);
			}
		} else {
			$log->debug(sprintf('Authentication failed for subject "%s"', $username));
			return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, $username, $messages);
		}
	}
}

?>
