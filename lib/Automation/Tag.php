<?php

/**
* @author Tim Rupp
*/
class Automation_Tag extends Automation_Abstract {
	const IDENT = __CLASS__;

	/**
	* @throws Automation_Exception
	* @return boolean
	*/
	public function add($tag) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		try {
			$data = array(
				'resource' => $tag
			);
			$db->insert('automations_tag', $data);
			return true;
		} catch (Exception $error) {
			throw new Automation_Exception($error->getMessage());
		}

		return false;
	}

	/**
	* @throws Automation_Exception
	*/
	public function delete($id) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		try {
			$where = $db->quoteInto('id = ?', $id);
			$db->delete('automations_tag', $where);
			return true;
		} catch (Exception $error) {
			throw new Automation_Exception($error->getMessage());
		}
	}

	/**
	* @throws Automation_Exception
	* @return boolean
	*/
	public function exists($tag) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select('id')
			->from('automations_tag')
			->where(sprintf('%s = %s',
				$db->quoteIdentifier('resource'),
				$db->quote($tag)
			))
			->limit(1);

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
			throw new Automation_Exception($error->getMessage());
		}
	}

	/**
	* @throws Automation_Exception
	* @return array
	*/
	public function get($resource = null, $page = 1, $limit = 15) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('automations_tag')
			->order('resource ASC');

		if ($resource !== null) {
			$sql->where('resource = ?', $resource);
		}

		if (!empty($limit)) {
			$sql->limitPage($page, $limit);
		}

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			$result = $stmt->fetchAll();
			return $result;
		} catch (Exception $error) {
			throw new Automation_Exception($error->getMessage());
		}
	}
}

?>
