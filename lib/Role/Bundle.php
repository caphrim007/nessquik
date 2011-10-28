<?php

/**
* @author Tim Rupp
*/
class Role_Bundle {
	protected $_roles;

	const IDENT = __CLASS__;

	public function __construct() {
		$this->reset();
	}

	public function filter($filter) {
		if (!in_array($filter, $this->_data['filters'])) {
			$this->_data['filters'][] = $filter;
		}
	}

	public function orFilter($filter) {
		if (!in_array($filter, $this->_data['orFilters'])) {
			$this->_data['orFilters'][] = $filter;
		}
	}

	public function reset($part = null) {
		if ($part === null) {
			$this->_data = array(
				'filters' => array(),
				'orFilters' => array(),
				'page' => 0,
				'limit' => 0
			);
		} else {
			switch($part) {
				case 'filters':
				case 'orFilters':
					$this->_data[$part] = array();
					break;
				case 'page':
					$this->_data['page'] = 0;
					break;
				case 'limit':
					$this->_data['limit'] = 0;
					break;
			}
		}
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
		$tags = array();
		$orTags = array();
		$filters = array();
		$orFilters = array();
		$date = new Zend_Date;

		$sql = $db->select()
			->from('roles')
			->order('name ASC');

		$filters = array_unique($this->_data['filters']);
		if (!empty($filters)) {
			foreach($filters as $filter) {
				if (strpos($filter, '*') !== false) {
					$filter = str_replace('*','%',$filter);
				} else {
					$filter .= '%';
				}

				$sql->where('name LIKE ?', $filter);
			}
		}

		$orFilters = array_unique($this->_data['orFilters']);
		if (!empty($orFilters)) {
			foreach($orFilters as $filter) {
				if (strpos($filter, '*') !== false) {
					$filter = str_replace('*','%',$filter);
				} else {
					$filter .= '%';
				}

				$sql->orWhere('name LIKE ?', $filter);
			}
		}

		if (!empty($this->_data['limit'])) {
			$sql->limitPage($this->_data['page'], $this->_data['limit']);
		}

		return $sql;
	}

	public function getNames() {
		return sort(array_values($this->_roles));
	}

	public function getIds() {
		return sort(array_keys($this->_roles));
	}

	public function count() {
		// Because the limit is used in the prepare query, I
		// save it here before I remove it for the "query all"
		// get method
		$limit = $this->_data['limit'];
		$this->limit(0);
		$results = $this->get();

		// And now I restore the previous limit so that future
		// calls to "get" will return the correct number of results
		$this->limit($limit);

		return count($results);
	}
}

?>
