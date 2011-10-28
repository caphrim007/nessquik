<?php

/**
* @author Tim Rupp
*/
class Api_Scanner {
	const IDENT = __CLASS__;

	/**
	* @param string $token Access token
	* @param string $scannerId ID of the scanner to get the configuration of
	* @return array|struct
	*/
	public function getParams($token, $scannerId) {
		$allowed = false;
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

			if (!$account->acl->isAllowed('Capability', 'use_any_scanner') && !$account->acl->isAllowed('Scanner', $scannerId)) {
				throw new Api_Exception(sprintf('Account %s is not allowed to view this scanners configuration', $account->username));
			}

			$server = new Audit_Server($scannerId);
			return $server->getParams();
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return array();
		}
	}

	/**
	* @param string $token Access token
	* @param string $scannerId ID of the scanner to get the configuration of
	* @return integer|string
	*/
	public function countRunning($token, $scannerId) {
		$allowed = false;
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

			if (!$account->acl->isAllowed('Capability', 'use_any_scanner') && !$account->acl->isAllowed('Scanner', $scannerId)) {
				throw new Api_Exception(sprintf('Account %s is not allowed to query this scanner', $account->username));
			}

			$server = new Audit_Server($scannerId);
			return $server->countRunning();
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return array();
		}
	}

	/**
	* @param string $token Access token
	* @param string $name Name of the new role
	* @param string $description Description of the new role
	* @return integer|string
	*/
	public function create($token, $name, $description = null) {
		$allowed = false;
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

			if (!$account->acl->isAllowed('Capability', 'edit_role')) {
				throw new Api_Exception(sprintf('Account %s is not allowed to create new roles', $account->username));
			}

			return Role_Util::create($name, $description);
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return null;
		}
	}

	/**
	* @param string $token Access token
	* @param string $roleId ID of the role to delete
	* @return boolean
	*/
	public function delete($token, $roleId) {
		$allowed = false;
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

			if (!$account->acl->isAllowed('Capability', 'edit_role')) {
				throw new Api_Exception(sprintf('Account %s is not allowed to modify this role', $account->username));
			}

			$role = new Role($roleId);
			return $role->delete();
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @param string $token Access token
	* @param integer|string $roleId ID of the role to change the name of
	* @param string $name New name of role
	* @return boolean
	*/
	public function setName($token, $roleId, $name) {
		$allowed = false;
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

			if (!$account->acl->isAllowed('Capability', 'edit_role')) {
				throw new Api_Exception(sprintf('Account %s is not allowed to modify this role', $account->username));
			}

			$role = new Role($roleId);
			return $role->setName($name);
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @param string $token Access token
	* @param integer|string $roleId ID of the role to change the description of
	* @param string $description
	* @return boolean
	*/
	public function setDescription($token, $roleId, $description) {
		$allowed = false;
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

			if (!$account->acl->isAllowed('Capability', 'edit_role')) {
				throw new Api_Exception(sprintf('Account %s is not allowed to modify this role', $account->username));
			}

			$role = new Role($roleId);
			return $role->setDescription($description);
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}
}

?>
