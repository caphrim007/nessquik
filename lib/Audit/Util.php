<?php
/** vim: set ts=4:sw=4:sts=4smarttab:expandtab:autoindent **/

/**
* @author Tim Rupp
*/
class Audit_Util {
	const IDENT = __CLASS__;

	/**
	* @param integer $page
	* @param integer $limit
	* @return array
	*/
	public static function getAudits($page = 1, $limit = 15) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('audits')
			->order('name ASC');

		if (!empty($limit)) {
			$sql->limitPage($page, $limit);
		}

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			return $stmt->fetchAll();
		} catch (Exception $error) {
			throw new Audit_Exception($error->getMessage());
		}
	}

	/**
	* @param string $status
	* @param integer $page
	* @param integer $limit
	* @return array
	*/
	public function get($status = 'P', $page = 1, $limit = 15) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);
		$helper = new App_Controller_Helper_InterpretStatus;
		$status = $helper->direct($status, true);

		$sql = $db->select()
			->from('accounts_audits_admin');

		if ($status !== null) {
			$sql->where(sprintf('%s = %s',
				$db->quoteIdentifier('audit_status'),
				$db->quote($status)
			));
		}

		if (!empty($limit)) {
			$sql->limitPage($page, $limit);
		}

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			return $stmt->fetchAll();
		} catch (Exception $error) {
			throw new Account_Exception($error->getMessage());
		}
	}

	public function count($status = 'P') {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);
		$helper = new App_Controller_Helper_InterpretStatus;
		$status = $helper->direct($status, true);

		$sql = $db->select()
			->from('accounts_audits_admin');

		if ($status !== null) {
			$sql->where(sprintf('%s = %s',
				$db->quoteIdentifier('audit_status'),
				$db->quote($status)
			));
		}

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			return $stmt->rowCount();
		} catch (Exception $error) {
			throw new Account_Exception($error->getMessage());
		}
	}

	public static function create() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);
		$date = new Zend_Date;
		$currentTime = $date->get(Zend_Date::W3C);
		$permissions = new Permissions;

		try {
			$record = array(
				'name' => 'New audit',
				'date_scheduled' => $currentTime,
				'created' => $currentTime,
				'last_modified' => $currentTime,
				'status' => 'N'
			);

			$log->debug('Inserting audit record into database');

			$result = $db->insert('audits', $record);

			if ($result > 0) {
				$log->debug('Inserted audit record into the database');
				$auditId = $db->lastInsertId('audits_id');

				$result = $permissions->add('Audit', $auditId);
				return $auditId;
			} else {
				$log->err('Failed to insert the new audit record into the database');
				return false;
			}
		} catch (Exception $error) {
			$log->err($error->getMessage());
			throw new Audit_Exception($error->getMessage());
		}
	}

	/**
	* @throws Audit_Exception
	*/
	public function exists($auditId) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('audits')
			->where('id = ?', $auditId);

		try {
			$log->debug($sql->__toString());

			$stmt = $sql->query();
			$result = $stmt->fetchAll();

			if (count($result) > 0) {
				return true;
			} else {
				return false;
			}
		} catch (Exception $error) {
			throw new Audit_Exception($error->getMessage());
		}
	}
}

?>
