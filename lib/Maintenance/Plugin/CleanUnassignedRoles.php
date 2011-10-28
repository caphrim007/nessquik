<?php

/**
* @author Tim Rupp
*/
class Maintenance_Plugin_CleanUnassignedRoles extends Maintenance_Plugin_Abstract {
	const IDENT = __CLASS__;

	/**
	* @throws Maintenance_Plugin_Exception
	*/
	public function dispatch(Maintenance_Request_Abstract $request) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);
		$date = new Zend_Date;
		$date->subHour(24);

		$sql = $db->select()
			->from('roles', array('id', 'name'))
			->joinLeft('accounts_roles', 'roles.id = accounts_roles.role_id', null)
			->where('accounts_roles.account_id IS NULL')
			->where('roles.immutable = ?', 0)
			->where('roles.last_modified <= ?', $date->get(Zend_Date::W3C));

		try {
			$log->debug($sql->__toString());
			$result = $sql->query();

			if (!empty($result)) {
				$log->debug(sprintf('Found %s unassigned role(s) to delete', count($result)));
				foreach($result as $role) {
					$log->debug(sprintf('Deleting role "%s" which has id "%s"', $role['name'], $role['id']));
					$where = $db->quoteInto('id = ?', $role['id']);
					$result = $db->delete('roles', $where);
					if ($result > 0) {
						$log->debug(sprintf('Successfully deleted %s unassigned role(s)', $result));
					} else {
						$log->err('Failed to delete unassigned roles');
					}
				}
			}
		} catch (Exception $error) {
			throw new Maintenance_Plugin_Exception($error->getMessage());
		}
	}
}

?>
