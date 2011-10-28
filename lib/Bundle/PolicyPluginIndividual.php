<?php

/**
* @author Tim Rupp
*/
class Bundle_PolicyPluginIndividual {
	protected $_data;

	const IDENT = __CLASS__;

	public function __construct() {
		$this->reset();
	}

	public function filter($filter, $type = 'name') {
		$this->_data['filter'] = array(
			'type' => $type,
			'filter' => $filter
		);
	}

	public function family($family) {
		$this->_data['family'] = $family;
	}

	public function reset($part = null) {
		$this->_data = array(
			'policyId' => 0,
			'family' => '',
			'filter' => array(),
			'page' => 0,
			'limit' => 0
		);
	}

	public function limit($limit) {
		if (is_numeric($limit)) {
			$this->_data['limit'] = $limit;
		}
	}

	public function policy($policyId) {
		if (is_numeric($policyId)) {
			$this->_data['policyId'] = $policyId;
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
		$filters = array();
		$orFilters = array();

		$sql = $db->select()
			->distinct()
			->from('plugins', array('id', 'name'))
			->joinLeft('policies_plugins_individual', 'plugins.id = policies_plugins_individual.plugin', array('state'))
			->where('plugins.family != ?', 'Port scanners')
			->order('plugins.name ASC');

		if (!empty($this->_data['filter'])) {
			$filter = $this->_data['filter'];
			switch ($filter['type']) {
				case 'id':
					$sql->where('plugins.id ILIKE ?', '%'.$filter.'%');
					break;
				case 'name':
					$sql->where('plugins.name ILIKE ?', '%'.$filter.'%');
					break;
			}
		}

		if (!empty($this->_data['family'])) {
			$sql->where('family = ?', $this->_data['family']);
		}

		if (!empty($this->_data['policyId'])) {
			$sql->where('policies_plugins_individual.policy_id = ? OR policies_plugins_individual.policy_id IS NULL', $this->_data['policyId']);
		} else {
			$sql->where('policies_plugins_individual.policy_id IS NULL');
		}

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
