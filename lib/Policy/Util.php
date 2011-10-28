<?php

/**
* @author Tim Rupp
*/
class Policy_Util {
	/**
	*
	*/
	const IDENT = __CLASS__;

	public static function getPolicies($page = 1, $limit = 15) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('policies')
			->order('name ASC');

		if (!empty($limit)) {
			$sql->limitPage($page, $limit);
		}

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			return $stmt->fetchAll();
		} catch (Exception $error) {
			throw new Policy_Exception($error->getMessage());
		}
	}

	public static function getPolicyById($id) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$policyPath = self::getPolicyPath($id);

		if (is_null($policyPath)) {
			$log->err(sprintf('Policy with id %s was not found', $id));
			return null;
		}

		$policy = file_get_contents(sprintf('%s/%s', _ABSPATH, $policyPath));

		return $policy;
	}

	public function create() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);
		$date = new Zend_Date;
		$currentTime = $date->get(Zend_Date::W3C);
		$permissions = new Permissions;

		$record = array(
			'name' => 'New policy',
			'created' => $currentTime,
			'last_modified' => $currentTime
		);

		try {
			$result = $db->insert('policies', $record);

			if ($result > 0) {
				$log->debug('Inserted policy record into the database');
				$policyId = $db->lastInsertId('policies_id');

				$result = $permissions->add('Policy', $policyId);

				return $policyId;
			} else {
				$log->err('Failed to insert the new policy record into the database');
				return false;
			}
		} catch (Exception $error) {
			throw new Policy_Exception($error->getMessage());
		}
	}

	/**
	* @throws Audit_Exception
	*/
	public function exists($policyId) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('policies')
			->where('id = ?', $policyId);

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

	public function count() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('policies');

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			return $stmt->rowCount();
		} catch (Exception $error) {
			throw new Account_Exception($error->getMessage());
		}
	}
}

?>
