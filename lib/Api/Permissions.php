<?php

/**
* @author Tim Rupp
*/
class Api_Permissions {
	const IDENT = __CLASS__;

	/**
	* Adds a new permission to the system.
	*
	* @param string $token Access token
	* @param string $permission Permission to be added
	* @param string $type the type of permission to add
	* @return boolean
	*/
	public function create($token, $permission, $type) {
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

			if (!$account->acl->isAllowed('Capability', 'edit_permission')) {
				throw new Api_Exception(sprintf('Account %s is not allowed to add new permissions', $account->username));
			}

			$permissions = new Permissions;

			switch($type) {
				case 'capability':
					if ($permissions->exists('Capability', $permission)) {
						return true;
					} else {
						return $permissions->add('Capability', $permission);
					}
					break;
				case 'hostname':
					if ($permissions->exists('HostnameTarget', $permission)) {
						return false;
					} else {
						return $permissions->add('HostnameTarget', $permission);
					}
					break;
				case 'network':
					if (Ip::isIpAddress($permission) || Ip::isRange($permission) || Ip::isCidr($permission)) {
						if ($permissions->exists('NetworkTarget', $permission)) {
							return false;
						} else {
							return $permissions->add('NetworkTarget', $permission);
						}
					} else {
						return false;
					}
					break;
				default:
					throw new Zend_XmlRpc_Server_Exception('Unknown permission type specified');
			}
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @param string $token Access token
	* @param integer|string $permissionId ID of the permission to delete
	* @param string $type The type of permission to delete
	* @return boolean
	*/
	public function delete($token, $permissionId, $type) {
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

			if (!$account->acl->isAllowed('Capability', 'edit_permission')) {
				throw new Api_Exception(sprintf('Account %s is not allowed to modify this permission', $account->username));
			}

			$permissions = new Permissions;

			switch ($type) {
				case 'capability':
					return $permissions->delete('Capability', $permissionId);
				case 'network':
					return $permissions->delete('NetworkTarget', $permissionId);
				case 'hostname':
					return $permissions->delete('HostnameTarget', $permissionId);
				default:
					throw new Api_Exception('Unknown permission type specified');
			}
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}
}

?>
