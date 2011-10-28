<?php

/**
* @author Tim Rupp
*/
class Api_Role {
	const IDENT = __CLASS__;

	/**
	* Assigns an existing permission to a role
	*
	* @param string $token Access token
	* @param integer|string $roleId ID of the role to assign the permission to
	* @param integer|string $permissionId ID of the permission to assign to the role
	* @return boolean
	*/
	public function assignPermission($token, $roleId, $permissionId) {
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
				throw new Api_Exception(sprintf('Account %s is not allowed to assign permissions to this role', $account->username));
			}

			$role = new Role($roleId);
			return $role->addPermission($permissionId);
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
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
	* @param string $role_id Role ID to fetch permissions for
	* @return array|struct
	*/
	public function getPermissions($token, $roleId) {
		$allowed = false;
		$results = array();
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
				throw new Api_Exception(sprintf('Account %s is not allowed to retrieve permissions for roles', $account->username));
			}

			$role = new Role($roleId);

			$result['ApiMethod'] = $role->get('ApiMethod', 0, 0);
			$result['Capability'] = $role->get('Capability', 0, 0);
			$result['NetworkTarget'] = $role->get('NetworkTarget', 0, 0);
			$result['HostnameTarget'] = $role->get('HostnameTarget', 0, 0);
			$result['Scanner'] = $role->get('Scanner', 0, 0);
			$result['Cluster'] = $role->get('Cluster', 0, 0);

			return $result;
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return array();
		}
	}

	/**
	* @param string $token Access token
	* @param string $roleName Name of the role to get the ID of
	* @return string
	*/
	public function getRoleId($token, $roleName) {
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
				throw new Api_Exception(sprintf('Account %s is not allowed to retrieve permissions for roles', $account->username));
			}

			return Role_Util::getRoleId($roleName);
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return null;
		}
	}

	/**
	* @param string $token Access token
	* @param array|string|struct $permissions Permission(s) to remove
	* @return boolean
	*/
	public function removePermission($token, $roleId, $permissions) {
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
				throw new Api_Exception(sprintf('Account %s is not allowed to remove permissions from roles', $account->username));
			}

			$role = new Role($roleId);

			if (!is_array($permissions)) {
				$permissions = array($permissions);
			}

			foreach($permissions as $permission) {
				$result = $role->deletePermission($permission);
			}

			return $result;
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
