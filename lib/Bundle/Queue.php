<?php

/**
* @author Tim Rupp
*/
class Bundle_Queue {
	protected $_data;

	const IDENT = __CLASS__;

	public function __construct() {
		$this->reset();
	}

	public function reset($part = null) {
		$this->_data = array(
			'page' => 0,
			'limit' => 0
		);
	}

	public function limit($limit) {
		if (is_numeric($limit)) {
			$this->_data['limit'] = $limit;
		}
	}

	public function page($page) {
		if (is_numeric($page)) {
			$this->_data['page'] = $page;
		}
	}

	public function __toString() {
		$sql = $this->_prepareQuery();
		return $sql->__toString();
	}

	public function get() {
		$log = App_Log::getInstance(self::IDENT);

		$sql = $this->_prepareQuery();
		$log->debug($sql->__toString());

		$stmt = $sql->query();
		$result = $stmt->fetchAll();

		return $result;
	}

	protected function _prepareQuery() {
		$config = Ini_Config::getInstance();
		$db = App_Db::getInstance($config->database->default);

		$subSql = $db->select()
			->from('message', array('queue_id', 'counter' => 'COUNT(*)'))
			->group('queue_id');

		$sql = $db->select()
			->from('queue', array('queue_name'))
			->joinLeft(array(
					'queue_count' => $subSql
				),
				sprintf('%s.%s = %s.%s',
					$db->quoteIdentifier('queue'),
					$db->quoteIdentifier('queue_id'),
					$db->quoteIdentifier('queue_count'),
					$db->quoteIdentifier('queue_id')
				), array('counter')
			)
			->order('queue_name ASC');

		if (!empty($this->_data['limit'])) {
			$sql->limitPage($this->_data['page'], $this->_data['limit']);
		}

		return $sql;
	}

	public function count($limit = true) {
		$sql = $this->_prepareQuery();

		if ($limit === false) {
			$sql->limit(0);
		}

		$stmt = $sql->query();
		$result = $stmt->fetchAll();
		return count($result);
	}
}

?>
