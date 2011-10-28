<?php

/**
* @author Tim Rupp
*/
class Policy_Plugins {
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

	public function familyDisabled($family) {

	}

	public function pluginDisabled($plugin) {
		$config = Ini_Config::getInstance();
		$db = App_Db::getInstance($config->database->default);

		$plugin = trim($plugin);

		$sql = $db->select()
			->from('policies_plugins_individual')
			->where('policy_id = ?', $this->_id)
			->where('plugin = ?', $plugin)
			->where('state = ?', 'disabled')
			->limit(1);

		$stmt = $sql->query();
		$result = $stmt->fetchAll();

		if ($result == 1) {
			return true;
		} else {
			return false;
		}
	}

	public function count() {

	}

	public function enableFamily($family) {
		$where = array();

		$config = Ini_Config::getInstance();
		$db = App_Db::getInstance($config->database->default);
		$family = trim($family);

		try {
			$db->beginTransaction();

			/**
			* First, remove any disabled plugins from the
			* policies_plugins_individual table for a particular
			* family because we are about to enable all the
			* plugins in that family
			*/
			$subSelect = $db->select()
				->from('plugins', 'id')
				->where('family = ?', $family);
			$where[] = $db->quoteInto('policy_id = ?', $this->_id);
			$where[] = $db->quoteInto('plugin IN ?', $subSelect);
			$db->delete('policies_plugins_individual', $where);

			/**
			* Now, remove the family from the policies_plugins_families
			* table. Any entries in this table should be for disabled
			* families, so removing the family essentially enables it.
			*/
			$where = array();
			$where[] = $db->quoteInto('policy_id = ?', $this->_id);
			$where[] = $db->quoteInto('family = ?', $family);
			$ok = $db->delete('policies_plugins_families', $where);

			$db->commit();

			/**
			* $profiler = $db->getProfiler();
			* foreach ($profiler->getQueryProfiles() as $query) {
			*	print_r($query);
			* }
			*/
		} catch (Exception $error) {
			$db->rollback();
		}
	}

	public function disableFamily($family) {
		$where = array();

		$config = Ini_Config::getInstance();
		$db = App_Db::getInstance($config->database->default);
		$family = trim($family);

		try {
			$db->beginTransaction();

			/**
			* First, remove any disabled plugins from the
			* policies_plugins_individual table for a particular
			* family because we are about to enable all the
			* plugins in that family
			*/
			$subSelect = $db->select()
				->from('plugins', 'id')
				->where('family = ?', $family);
			$where[] = $db->quoteInto('policy_id = ?', $this->_id);
			$where[] = $db->quoteInto('plugin IN ?', $subSelect);
			$db->delete('policies_plugins_individual', $where);

			/**
			* In case the family somehow is already in the table,
			* first remove it.
			*/
			$where = array();
			$where[] = $db->quoteInto('policy_id = ?', $this->_id);
			$where[] = $db->quoteInto('family = ?', $family);
			$db->delete('policies_plugins_families', $where);

			/**
			* Now, to disable the family, insert it into the
			* policies_plugins_families table with a state
			* value of disabled
			*/
			$data = array(
				'state' => 'disabled',
				'family' => $family,
				'policy_id' => $this->_id
			);
			$db->insert('policies_plugins_families', $data);

			$db->commit();

			/**
			* $profiler = $db->getProfiler();
			* foreach ($profiler->getQueryProfiles() as $query) {
			*	print_r($query);
			* }
			*/
		} catch (Exception $error) {
			$db->rollback();
		}
	}

	public function enablePlugin($pluginId) {
		$where = array();

		$config = Ini_Config::getInstance();
		$db = App_Db::getInstance($config->database->default);
		$log = App_Log::getInstance(self::IDENT);

		$pluginId = trim($pluginId);
		$family = Plugin_Util::getFamily($pluginId);

		if ($family === null) {
			$log->err('The provided plugin was not found in any family');
			return;
		}

		/**
		* Now, count the number of currently disabled plugins
		* that are in that family
		*/
		$sql = $db->select()
			->from('policies_plugins_individual', 'count(*)')
			->joinLeft('plugins', 'policies_plugins_individual.plugin = plugins.id', null)
			->where('policies_plugins_individual.policy_id = ?', $this->_id)
			->where('plugins.family = ?', $family);
		$stmt = $sql->query();
		$result = $stmt->fetch();
		$currentCount = $result['count'];
		$currentCount = $currentCount - 1;

		if ($currentCount == 0) {
			return $this->enableFamily($family);
		} else {
			try {
				$db->beginTransaction();

				/**
				* Assuming the total number of disabled plugins
				* is not about to equal the total number of plugins
				* in the family, then delete any potential existing
				* plugins with that ID from the policies_plugins_individual
				* table.
				*/
				$where = array();
				$where[] = $db->quoteInto('policy_id = ?', $this->_id);
				$where[] = $db->quoteInto('plugin = ?', $pluginId);
				$db->delete('policies_plugins_individual', $where);

				$db->commit();
			} catch (Exception $error) {
				$db->rollback();
			}
		}
	}

	/**
	* The following needs to occur in this method
	*
	* - If the total number of plugins in the family is
	*   equal to the total number of disabled plugins + 1
	*     - disable the family instead
	* - else
	*   - Insert the plugin in the policies_plugins_individual
	*     table with a state of disabled
	*/
	public function disablePlugin($pluginId) {
		$where = array();

		$config = Ini_Config::getInstance();
		$db = App_Db::getInstance($config->database->default);
		$log = App_Log::getInstance(self::IDENT);

		$pluginId = trim($pluginId);
		$family = Plugin_Util::getFamily($pluginId);

		if ($family === null) {
			$log->err('The provided plugin was not found in any family');
			return;
		}

		/**
		* Fetch a count of the number of plugins in the
		* family that the requested plugin is in
		*/
		$sql = $db->select()
			->from('stat_plugins_in_family')
			->where('family = ?', $family);
		$stmt = $sql->query();
		$result = $stmt->fetch();
		$pluginCount = $result['count'];

		/**
		* Now, count the number of currently disabled plugins
		* that are in that family
		*/
		$sql = $db->select()
			->from('policies_plugins_individual', 'count(*)')
			->joinLeft('plugins', 'policies_plugins_individual.plugin = plugins.id', null)
			->where('policies_plugins_individual.policy_id = ?', $this->_id)
			->where('plugins.family = ?', $family);
		$stmt = $sql->query();
		$result = $stmt->fetch();
		$currentCount = $result['count'];
		$currentCount = $currentCount + 1;

		if ($currentCount == $pluginCount) {
			return $this->disableFamily($family);
		} else {
			try {
				$db->beginTransaction();

				/**
				* Assuming the total number of disabled plugins
				* is not about to equal the total number of plugins
				* in the family, then delete any potential existing
				* plugins with that ID from the policies_plugins_individual
				* table.
				*/
				$where = array();
				$where[] = $db->quoteInto('policy_id = ?', $this->_id);
				$where[] = $db->quoteInto('plugin = ?', $pluginId);
				$db->delete('policies_plugins_individual', $where);

				/**
				* Now, add the plugin with a state of disabled to
				* the policies_plugins_individual table
				*/
				$data = array(
					'policy_id' => $this->_id,
					'plugin' => $pluginId,
					'state' => 'disabled'
				);
				$db->insert('policies_plugins_individual', $data);

				/**
				* Disabling a plugin from a family set's that families
				* state to 'mixed'. Fixed, clear the existing family
				* if it is there.
				*/
				$where = array();
				$where[] = $db->quoteInto('policy_id = ?', $this->_id);
				$where[] = $db->quoteInto('family = ?', $family);
				$db->delete('policies_plugins_families', $where);

				/**
				* Then, insert the new mixed entry into the families table
				*/
				$data = array(
					'policy_id' => $this->_id,
					'family' => $family,
					'state' => 'mixed'
				);
				$db->insert('policies_plugins_families', $data);

				$db->commit();
			} catch (Exception $error) {
				$db->rollback();
			}
		}
	}
}

?>
