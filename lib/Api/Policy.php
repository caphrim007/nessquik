<?php

/**
* @author Tim Rupp
*/
class Api_Policy {
	const IDENT = __CLASS__;

	/**
	* @param string $token Access token
	* @param string $policyId ID of the policy to add a plugin to
	* @param integer|string $plugin Plugin to include
	* @param string $type Type of plugin being added. Can be individual, family, or category
	* @return boolean
	*/
	public function includePlugin($token, $policyId, $plugin, $type) {
		$allowed = false;
		$log = App_Log::getInstance(self::IDENT);

		try {
			if (empty($policyId)) {
				throw new Api_Exception('The policy ID provided to the controller was empty');
			}

			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			if ($account->acl->isAllowed('Policy', $policyId) || $account->acl->isAllowed('Capability', 'edit_policy')) {
				$policy = new Audit_Policy($policyId);
			} else {
				throw new Api_Exception(sprintf('Account %s is not allowed to modify this policy', $account->username));
			}

			switch($type) {
				case 'individual':
				case 'family':
				case 'category':
					$policy->includePlugin($plugin, $type);
					$policy->writeDraft();
					break;
				default:
					throw new Api_Exception('Unknown plugin type specified');
			}

			return true;
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

			$defaultPolicy = $account->policy->getDefaultPolicyId();

			try {
				$policy = new Audit_Policy($defaultPolicy);
				if ($policy->exists()) {
					$policyId = Audit_Policy_Util::create($defaultPolicy);
				} else {
					$log->info('Default policy does not exist either in database or on disk');
					$log->info('Using system default policy to create new policy');
					$policyId = Audit_Policy_Util::create();
				}
			} catch (Exception $error) {
				$log->debug('Default policy for account may have been erased. Creating new one based off of system default');
				$policyId = Audit_Policy_Util::create();
			}

			unset($policy);

			if ($policyId === false) {
				throw new Zend_Controller_Action_Exception('Could not create the new audit policy');
			}

			$policy = new Audit_Policy($policyId);
			$permission = new Permissions;

			$permission = $permission->get('Policy', $policyId);
			$result = $account->acl->allow($permission[0]['permission_id']);

			$policy->draft();

			return $policyId;
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return null;
		}
	}

	/**
	* @param string $token Access token
	* @param string $policyId The ID of the policy to delete
	* @return boolean
	*/
	public function delete($token, $policyId) {
		$allowed = false;
		$log = App_Log::getInstance(self::IDENT);

		try {
			if (empty($policyId)) {
				throw new Api_Exception('The policy ID provided to the controller was empty');
			}

			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			if ($account->acl->isAllowed('Policy', $policyId) || $account->acl->isAllowed('Capability', 'edit_policy')) {
				$policy = new Audit_Policy($policyId);
			} else {
				throw new Api_Exception(sprintf('Account %s is not allowed to modify this policy', $account->username));
			}

			$result = $policy->deleteDraft();
			$result = $policy->delete();
			return true;
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @param string $token Access token
	* @param string $policyId ID of the policy to exclude the plugin from
	* @param integer|string $plugin Plugin to exclude
	* @param string $type Type of plugin being added. Can be individual, family, or category
	* @return boolean
	*/
	public function excludePlugin($token, $policyId, $plugin, $type) {
		$allowed = false;
		$log = App_Log::getInstance(self::IDENT);

		try {
			if (empty($policyId)) {
				throw new Api_Exception('The policy ID provided to the controller was empty');
			}

			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			if ($account->acl->isAllowed('Policy', $policyId) || $account->acl->isAllowed('Capability', 'edit_policy')) {
				$policy = new Audit_Policy($policyId);
			} else {
				throw new Api_Exception(sprintf('Account %s is not allowed to modify this policy', $account->username));
			}

			switch($type) {
				case 'individual':
				case 'family':
				case 'category':
					$policy->excludePlugin($plugin, $type);
					$policy->writeDraft();
					break;
				default:
					throw new Api_Exception('Unknown plugin type specified');
			}

			return true;
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @param string $token Access token
	* @param string $policyId ID of the policy to get a list of plugins for
	* @param string $status Status of the plugins to return; enabled or disabled
	* @return array|struct
	*/
	public function getPlugins($token, $policyId, $status = 'null') {
		$allowed = false;
		$log = App_Log::getInstance(self::IDENT);

		try {
			if (empty($policyId)) {
				throw new Api_Exception('The policy ID provided to the controller was empty');
			}

			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			if ($account->acl->isAllowed('Policy', $policyId) || $account->acl->isAllowed('Capability', 'edit_policy')) {
				$policy = new Audit_Policy($policyId);
			} else {
				throw new Api_Exception(sprintf('Account %s is not allowed to read this policy', $account->username));
			}

			if ($status == 'enabled' || $status === null) {
				$plugins['enabled']['individual'] = $policy->getIndividualPlugins('enabled');
				$plugins['enabled']['family'] = $policy->getPluginFamilies('enabled');
				$plugins['enabled']['category'] = $policy->getPluginCategories('enabled');
			}

			if ($status == 'disabled' || $status === null) {
				$plugins['disabled']['individual'] = $policy->getIndividualPlugins('disabled');
				$plugins['disabled']['family'] = $policy->getPluginFamilies('disabled');
				$plugins['disabled']['category'] = $policy->getPluginCategories('disabled');
			}

			return $plugins;
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return array();
		}
	}

	/**
	* @param string $token Access token
	* @param string $policyId ID of the policy to remove a plugin from
	* @param integer|string $plugin Plugin to remove
	* @param string $type Type of plugin being added. Can be individual, family, or category
	* @return boolean
	*/
	public function removePlugin($token, $policyId, $plugin, $type) {
		$allowed = false;
		$log = App_Log::getInstance(self::IDENT);

		try {
			if (empty($policyId)) {
				throw new Api_Exception('The policy ID provided to the controller was empty');
			}

			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			if ($account->acl->isAllowed('Policy', $policyId) || $account->acl->isAllowed('Capability', 'edit_policy')) {
				$policy = new Audit_Policy($policyId);
			} else {
				throw new Api_Exception(sprintf('Account %s is not allowed to modify this policy', $account->username));
			}

			switch($type) {
				case 'individual':
				case 'family':
				case 'category':
					$policy->removePlugin($plugin, $type);
					$policy->writeDraft();
					break;
				default:
					throw new Api_Exception('Unknown plugin type specified');
			}

			return true;
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @param string $token Access token
	* @param string $policyId ID of the policy to save
	* @return boolean
	*/
	public function save($token, $policyId) {
		$allowed = false;
		$log = App_Log::getInstance(self::IDENT);

		try {
			if (empty($policyId)) {
				throw new Api_Exception('The policy ID provided to the controller was empty');
			}

			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			if ($account->acl->isAllowed('Policy', $policyId) || $account->acl->isAllowed('Capability', 'edit_policy')) {
				$policy = new Audit_Policy($policyId);
			} else {
				throw new Api_Exception(sprintf('Account %s is not allowed to modify this policy', $account->username));
			}

			$policy->write();
			$policy->deleteDraft();
			return true;
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @param string $token Access token
	* @param string $type The type of preference to set
	* @param string $policyId ID of the policy to set preferences on
	* @param string $preference Preference to set
	* @param string $value Value to set the preference to
	* @return boolean
	*/
	public function setPreference($token, $type, $policyId, $preference, $value = null) {
		$allowed = false;
		$log = App_Log::getInstance(self::IDENT);

		try {
			if (empty($policyId)) {
				throw new Api_Exception('The policy ID provided to the controller was empty');
			}

			if (empty($type)) {
				throw new Api_Exception('The preference type provided to the controller was empty');
			}

			if (empty($preference)) {
				throw new Api_Exception('The preference provided to the controller was empty');
			}

			$account = Api_Util::getAccount($token);
			if ($account === null) {
				throw new Api_Exception(sprintf('No account could be mapped to the token %s', $token));
			}

			$allowed = Api_Util::isAllowed($account);
			if (!$allowed) {
				throw new Api_Exception(sprintf('Account %s is not allowed to call this method', $account->username));
			}

			if ($account->acl->isAllowed('Policy', $policyId) || $account->acl->isAllowed('Capability', 'edit_policy')) {
				$policy = new Audit_Policy($policyId);
			} else {
				throw new Api_Exception(sprintf('Account %s is not allowed to modify this policy', $account->username));
			}

			switch($type) {
				case 'server':
					$result = $policy->setServerPreference($preference, $value);
					if ($result === false) {
						throw new Api_Exception('Failed to set policy server preference');
					}
					break;
				case 'general':
					$result = $policy->setGeneralPreference($preference, $value);
					if ($result === false) {
						throw new Api_Exception('Failed to set policy general preference');
					}
					break;
				case 'plugin':
					$result = $policy->setPluginPreference($preference, $value);
					if ($result === false) {
						throw new Api_Exception('Failed to set policy plugin preference');
					}
					break;
				default:
					throw new Api_Exception('Unknown preference type specified');
			}

			if ($result === true) {
				$policy->writeDraft();
				return true;
			} else {
				return false;
			}
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	/**
	* @param string $token Access token
	* @param integer|string $page Page of policies to get
	* @param integer|string $limit Number of results per page
	* @return array|struct
	*/
	public function getPolicies($token, $page = 1, $limit = 30) {
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

			return Audit_Policy_Util::getPolicies($page, $limit);
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return array();
		}
	}
}

?>
