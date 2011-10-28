<?php

/**
* @author Tim Rupp
*/
class Audit_Target {
	const IDENT = __CLASS__;

	protected $_id;

	public function __construct($id) {
		$this->_id = $id;
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

	public function removeTarget($targetId) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$where[] = $db->quoteInto('audit_id = ?', $this->_id);
		$where[] = $db->quoteInto('id = ?', $targetId);

		$result = $db->delete('audits_targets', $where);
	}

	public function includeTarget($target, $type) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$data = array(
			'target' => $target,
			'type' => $type,
			'audit_id' => $this->_id,
			'action' => 'include'
		);

		$db->insert('audits_targets', $data);
	}

	public function excludeTarget($target, $type) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$data = array(
			'target' => $target,
			'type' => $type,
			'audit_id' => $this->_id,
			'action' => 'exclude'
		);

		$db->insert('audits_targets', $data);
	}

	public function getTargets($page = 1, $limit = 15, $action = 'include') {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);
		$results = array();

		try {
			$sql = $db->select()
				->from('audits_targets', array('id', 'target', 'type'))
				->where('audit_id = ?', $this->_id)
				->where('action = ?', $action);

			$sql->order('target ASC');

			if (!empty($limit)) {
				$sql->limitPage($page, $limit);
			}

			$log->debug($sql->__toString());

			$stmt = $sql->query();
			return $stmt->fetchAll();
		} catch (Exception $error) {
			throw new Account_Exception($error->getMessage());
		}
	}

	public function count($status) {
		switch($status) {
			case 'include':
			case 'exclude':
				return count($this->getTargets(null,null,$status));
			default:
				return count($this->getTargets(null,null,null));
		}
	}
}

?>
