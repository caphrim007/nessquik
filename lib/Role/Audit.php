<?php

/**
* @author Tim Rupp
*/
class Role_Audit extends Role_Abstract {
	const IDENT = __CLASS__;

	/**
	* @throws Role_Exception
	*/
	public function get($page = 1, $limit = 15) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		if (!is_numeric($this->roleId)) {
			throw new Role_Exception('The supplied Role ID is invalid');
		}

		$sql = $db->select()
			->from('roles_permissions', null)
			->join('permissions_audit',
				sprintf('%s.%s = %s.%s', 
					$db->quoteIdentifier('roles_permissions'),
					$db->quoteIdentifier('permission_id'),
					$db->quoteIdentifier('permissions_audit'),
					$db->quoteIdentifier('id')
				),
				array('id', 'resource')
			)
			->join('audits', sprintf('%s.%s = %s.%s',
				$db->quoteIdentifier('permissions_audit'),
				$db->quoteIdentifier('resource'),
				$db->quoteIdentifier('audits'),
				$db->quoteIdentifier('id')
				),
				array('name')
			)
			->where(sprintf('%s.%s = %s',
				$db->quoteIdentifier('roles_permissions'),
				$db->quoteIdentifier('role_id'),
				$db->quote($this->roleId)
			))
			->order('permissions_audit.resource ASC');

		if (!empty($limit)) {
			$sql->limitPage($page, $limit);
		}

		try {
			$log->debug($sql->__toString());

			$stmt = $sql->query();
			$result = $stmt->fetchAll();
			if (count($result) > 0) {
				return $result;
			} else {
				return array();
			}
		} catch (Exception $error) {
			throw new Role_Exception($error->getMessage());
		}
	}

	public function getIds() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);
		$tmp = array();

		if (!is_numeric($this->roleId)) {
			throw new Role_Exception('The supplied Role ID is invalid');
		}

		$sql = $db->select()
			->from('roles_permissions', null)
			->join('permissions_audit',
				sprintf('%s.%s = %s.%s', 
					$db->quoteIdentifier('roles_permissions'),
					$db->quoteIdentifier('permission_id'),
					$db->quoteIdentifier('permissions_audit'),
					$db->quoteIdentifier('id')
				),
				array('id')
			)
			->where(sprintf('%s.%s = %s',
				$db->quoteIdentifier('roles_permissions'),
				$db->quoteIdentifier('role_id'),
				$db->quote($this->roleId)
			))
			->order('permissions_audit.resource ASC');

		try {
			$log->debug($sql->__toString());

			$stmt = $sql->query();
			$result = $stmt->fetchAll();

			if (is_array($result)) {
				foreach($result as $key => $val) {
					$tmp[] = $val['id'];
				}

				return $tmp;
			} else {
				return array();
			}
		} catch (Exception $error) {
			throw new Role_Exception($error->getMessage());
		}
	}
}

?>
