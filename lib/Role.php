<?php

/**
* @author Tim Rupp
*/
class Role {
	const IDENT = __CLASS__;

	protected $_id;
	protected $_data;
	protected $roles;

	public function __construct($id) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		if (!is_numeric($id)) {
			throw new Exception('Role ID must be a number');
		} else {
			$this->_id = $id;
		}

		$sql = $db->select()
			->from('roles')
			->where('id = ?', $this->_id);

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			$result = $stmt->fetchAll();
			if (!empty($result)) {
				foreach($result[0] as $key => $val) {
					$this->$key = $val;
				}
			}

		} catch (Exception $error) {
			throw new Audit_Exception($error->getMessage());
		}
	}

	public function __set($key, $val) {
		switch($key) {
			case 'id':
				return false;
		}

		$this->_data[$key] = $val;
	}

	public function __get($key) {
		switch($key) {
			case 'id':
				return $this->_id;
			default:
				break;
		}

		if (isset($this->_data[$key])) {
			return $this->_data[$key];
		} else {
			return false;
		}
	}

	/**
	* @throws Role_Exception
	*/
	public function get($type, $page = 1, $limit = 15) {
		if (isset($this->roles[$type])) {
			$role = $this->roles[$type];
		} else {
			$class = 'Role_'.$type;
			$role = new $class($this->_id);
		}

		if ($role instanceof Role_Abstract) {
			return $role->get($page, $limit);
		} else {
			throw new Role_Exception('The supplied role type is invalid');
		}
	}

	public function update() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		try {
			$where = $db->quoteInto('id = ?', $this->_id);
			$result = $db->update('roles', $this->_data, $where);
			return true;
		} catch (Exception $error) {
			throw new Role_Exception($error->getMessage());
		}
	}

	public function addPermission($permission) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		if (!is_numeric($this->_id)) {
			throw new Role_Exception('The supplied Role ID is invalid');
		} else if (empty($permission)) {
			throw new Role_Exception('The supplied permission was empty');
		} else if (!is_numeric($permission)) {
			throw new Role_Exception('The supplied permission was not a numeric value');
		}

		try {
			$data = array(
				'role_id' => $this->id,
				'permission_id' => $permission
			);
			$result = $db->insert('roles_permissions', $data);
			return true;
		} catch (Exception $error) {
			throw new Role_Exception($error->getMessage());
		}
	}

	public function deletePermission($permission) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		if (!is_numeric($this->_id)) {
			throw new Role_Exception('The supplied Role ID is invalid');
		}

		if (empty($permission)) {
			throw new Role_Exception('The supplied permission was empty');
		}

		try {
			$where[] = $db->quoteInto('role_id = ?', $this->id);
			$where[] = $db->quoteInto('permission_id = ?', $permission);

			$result = $db->delete('roles_permissions', $where);
			return true;
		} catch (Exception $error) {
			throw new Role_Exception($error->getMessage());
		}
	}

	public function clearPermissions() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		if (!is_numeric($this->_id)) {
			throw new Role_Exception('The supplied Role ID is invalid');
		}

		try {
			$where = $db->quoteInto('role_id = ?', $this->id);
			$result = $db->delete('roles_permissions', $where);
			return true;
		} catch (Exception $error) {
			throw new Role_Exception($error->getMessage());
		}
	}

	/**
	* @throws Role_Exception
	*/
	public function delete() {
		$session = Zend_Registry::get('nessquik');
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		try {
			$where = $db->quoteInto('id = ?', $this->_id);
			$result = $db->delete('roles', $where);

			if (isset($session->siteAudit['roleId'])) {
				if ($this->id == $session->siteAudit['roleId']) {
					$session->siteAudit = null;
					Zend_Registry::set('nessquik', $session);
				}
			}

			return true;
		} catch (Exception $error) {
			throw new Role_Exception($error->getMessage());
		}
	}
}

?>
