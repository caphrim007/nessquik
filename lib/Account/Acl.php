<?php

/**
* @author Tim Rupp
*/
class Account_Acl {
	/**
	* @var integer
	*/
	protected $accountId;

	/**
	* The primary role assigned to the account. This role
	* is what automated stuff usually gets assigned to.
	*/
	protected $primaryRole;

	/**
	* @var string
	*/
	const IDENT = __CLASS__;

	/**
	*
	*/
	public function __construct($accountId, $primaryRole) {
		if (is_numeric($accountId)) {
			$this->accountId = $accountId;
		} else {
			$this->accountId = 0;
		}

		if (is_numeric($primaryRole)) {
			$this->primaryRole = $primaryRole;
		} else {
			$this->primaryRole = 0;
		}
		$this->acl = array();
	}

	/**
	* @throws Account_Acl_Exception
	* @return boolean
	*/
	public function isAllowed($type = null, $resource = null) {
		$result = false;

		$class = 'Account_Acl_'.$type;
		$permission = new $class($this->accountId);

		if ($permission instanceof Account_Acl_Abstract) {
			return $permission->isAllowed($resource);
		} else {
			throw new Account_Acl_Exception('The supplied resource type is invalid');
		}
	}

	public function enumerate($type = null) {
		$results = array();
		$forbidden = array('Abstract', 'Broker', 'Exception', 'Test');

		if($type === null) {
			$dir = new DirectoryIterator(sprintf('%s/lib/Account/Acl', _ABSPATH));
			foreach($dir as $file ) {
				if(!$file->isDot() && !$file->isDir()) {
					$type = basename($file->getPathname(), '.php');
					$class = sprintf('Account_Acl_%s', $type);

					if (in_array($type, $forbidden)) {
						continue;
					}

					$permission = new $class($this->accountId);
					$results[$type] = $permission->get();

				}
			}

			return $results;
		} else {
			$class = 'Account_Acl_'.$type;
			$permission = new $class($this->accountId);
			$results[$type] = $permission->get();
			return $results;
		}
	}

	/**
	* @throws Account_Acl_Exception
	*/
	public function allow($permissionId) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$data = array(
			'permission_id' => $permissionId,
			'role_id' => $this->primaryRole
		);

		try {
			$result = $db->insert('roles_permissions', $data);
		} catch (Exception $error) {
			throw new Account_Acl_Exception($error->getMessage());
		}
	}
}

?>
