<?php

/**
* @author Tim Rupp
*/
class Policy {
	const IDENT = __CLASS__;

	protected $_id;
	protected $_data;

	public $plugins;
	public $preferences;

	public function __construct($id) {
		$config = Ini_Config::getInstance();
		$logger = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$this->_id = $id;

		$sql = $db->select()
			->from('policies')
			->where('id = ?', $this->_id);

		try {
			$logger->debug($sql->__toString());
			$stmt = $sql->query();
			$result = $stmt->fetchAll();
			if (!empty($result)) {
				foreach($result[0] as $key => $val) {
					$this->$key = $val;
				}
			}

			$this->plugins = new Policy_Plugins($this->_id);
			$this->preferences = new Policy_Preferences($this->_id);
		} catch (Exception $error) {
			throw new Audit_Exception($error->getMessage());
		}
	}

	public function __get($key) {
		switch($key) {
			case 'policy_name':
				$key = 'name';
				break;
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

	public function __set($key, $val) {
		switch($key) {
			case 'policy_name':
			case 'name':
				$key = 'name';
				break;
			case 'description':
				return;
				break;
			case 'created':
			case 'last_modified':
				if ($val instanceof Zend_Date) {
					break;
				} else {
					$val = new Zend_Date($val);
				}
				break;
			case 'id':
				return false;
		}

		$this->_data[$key] = $val;
	}

	public function delete() {
		$config = Ini_Config::getInstance();
		$db = App_Db::getInstance($config->database->default);

		try {
			$where = $db->quoteInto('id = ?', $this->_id);
			$result = $db->delete('policies', $where);

			return true;
		} catch (Exception $error) {
			throw new Audit_Exception($error->getMessage());
		}
	}

	public function update() {
		$config = Ini_Config::getInstance();
		$db = App_Db::getInstance($config->database->default);

		$this->last_modified = new Zend_Date;

		foreach($this->_data as $key => $val) {
			switch($key) {
				case 'created':
				case 'last_modified':
					if ($val instanceof Zend_Date) {
						$data[$key] = $val->get(Zend_Date::W3C);
					}
					break;
				default:
					$data[$key] = $val;
					break;
			}
		}

		$result = $this->preferences->update();

		$where = $db->quoteInto('id = ?', $this->_id);
		$result = $db->update('policies', $data, $where);
	}

	public function getOwner() {
		$config = Ini_Config::getInstance();
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('policy_ownership')
			->where('policy_id = ?', $this->_id);

		$stmt = $sql->query();
		$result = $stmt->fetch();

		if (empty($result)) {
			return false;
		} else {
			return new Account($result['account_id']);
		}
	}

	public function getPreferencesKv(Audit_Server $scanner) {
		$results = array();
		$cachedFamilyPlugins = array();

		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);

		$include = $this->getPluginSeletion(0);
		$exclude = $this->getPluginExclusion(0);

		// Merge in plugins
		if (isset($doc['plugin_selection'])) {
			$plugins = $doc['plugin_selection'];

			if (isset($plugins['all'])) {
				$log->debug('All plugins were chosen. Will include all families');

				$pluginFamilies = $scanner->adapter->listPlugins();
				foreach($pluginFamilies as $family) {
					$name = sprintf('plugin_selection.family.%s', $family['familyName']);
					$results[$name] = 'enabled';
				}
			}

			if (isset($plugins['family'])) {
				$log->debug(sprintf('"%s" families were specified', count($plugins['family'])));

				foreach($plugins['family'] as $plugin) {
					$name = sprintf('plugin_selection.family.%s', $plugin);
					$results[$name] = 'enabled';
				}
			}

			if (isset($plugins['individual_plugin'])) {
				$log->debug(sprintf('"%s" individual plugins were specified', count($plugins['individual_plugin'])));

				foreach($plugins['individual_plugin'] as $pluginId) {
					$plugin = new Audit_Plugins_Plugin($pluginId);

					if ($plugin->script != '') {
						$info = $scanner->adapter->lookupPlugin($plugin->script);
						$family = sprintf('plugin_selection.family.%s', $info['pluginFamily']);
						$results[$family] = 'mixed';

						if (isset($cachedFamilyPlugins[$family])) {
							$pif = $cachedFamilyPlugins[$family];
						} else {
							$log->debug(sprintf('Getting list of plugins in family "%s"', $info['pluginFamily']));
							$pif = $scanner->adapter->listPluginsInFamily($info['pluginFamily']);
							$cachedFamilyPlugins[$family] = $pif;
						}

						foreach($pif as $plu) {
							$name = sprintf('plugin_selection.individual_plugin.%s', $plu['pluginID']);

							if (isset($results[$name])) {
								if ($results[$name] == 'enabled') {
									continue;
								} else if ($plu['pluginID'] == $plugin->id) {
									$results[$name] = 'enabled';
								} else {
									$results[$name] = 'disabled';
								}
							} else {
								if ($plu['pluginID'] == $plugin->id) {
									$results[$name] = 'enabled';
								} else {
									$results[$name] = 'disabled';
								}
							}
						}
					} else {
						$log->info(sprintf('The script name was not set for the plugin with id "%s"', $pluginId));
					}
				}
			}
		}

		// Merge in plugin exclusions
		if (isset($doc['plugin_exclusion'])) {
			$plugins = $doc['plugin_exclusion'];

			if (empty($plugins)) {
				$log->debug('No plugin exclusions were specified');
			} else {
				$log->debug('Plugin exclusions were specified. Will merge them in now');

				if (isset($plugins['family'])) {
					$log->debug(sprintf('"%s" families were asked to be excluded', count($plugins['family'])));

					foreach($plugins['family'] as $plugin) {
						$name = sprintf('plugin_selection.family.%s', $plugin);
						$results[$name] = 'disabled';
					}
				}

				if (isset($plugins['individual_plugin'])) {
					$log->debug(sprintf('"%s" individual plugins asked to be excluded', count($plugins['individual_plugin'])));

					foreach($plugins['individual_plugin'] as $pluginId) {
						$plugin = new Audit_Plugins_Plugin($pluginId);

						if ($plugin->script != '') {
							$info = $scanner->adapter->lookupPlugin($plugin->script);
							$family = sprintf('plugin_selection.family.%s', $info['pluginFamily']);
							$results[$family] = 'mixed';

							if (isset($cachedFamilyPlugins[$family])) {
								$pif = $cachedFamilyPlugins[$family];
							} else {
								$log->debug(sprintf('Getting list of plugins in family "%s"', $info['pluginFamily']));
								$pif = $scanner->adapter->listPluginsInFamily($info['pluginFamily']);
								$cachedFamilyPlugins[$family] = $pif;
							}

							foreach($pif as $plu) {
								$name = sprintf('plugin_selection.individual_plugin.%s', $plu['pluginID']);

								if (isset($results[$name])) {
									if ($results[$name] == 'disabled') {
										continue;
									} else if ($plu['pluginID'] == $plugin->id) {
										$results[$name] = 'disabled';
									} else {
										$results[$name] = 'enabled';
									}
								} else {
									if ($plu['pluginID'] == $plugin->id) {
										$results[$name] = 'disabled';
									} else {
										$results[$name] = 'enabled';
									}
								}
							}
						} else {
							$log->info(sprintf('The script name was not set for the plugin with id "%s"', $plugin));
						}
					}
				}

				$log->debug('Removing disabled plugins from preferences list');
				foreach($results as $key => $result) {
					if ($result == 'disabled') {
						$log->debug(sprintf('Disabling %s', $key));
						unset($results[$key]);
					}
				}
			}
		}

		$results = array_filter($results);
		$results = array_merge($results, $doc['preferences']);

		$results['policy_shared'] = 1;
		$results['policy_name'] = $this->name;
		return $results;
	}

	public function getPluginSelection($page = 1, $limit = 15) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('policies_plugins')
			->where('action = ?', 'include')
			->where('id = ?', $this->_id);

		if (!empty($limit)) {
			$sql->limitPage($page, $limit);
		}

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			return $stmt->fetchAll();
		} catch (Exception $error) {
			throw new Exception($error->getMessage());
		}
	}

	public function getPluginExclusion($page = 1, $limit = 15) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('policies_plugins')
			->where('action = ?', 'exclude')
			->where('id = ?', $this->_id);

		if (!empty($limit)) {
			$sql->limitPage($page, $limit);
		}

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			return $stmt->fetchAll();
		} catch (Exception $error) {
			throw new Exception($error->getMessage());
		}
	}

	protected function _isValidPluginType($type) {
		$type = trim($type);
		switch($type) {
			case 'individual_plugin':
			case 'all':
			case 'family':
			case 'category':
				return true;
			default:
				return false;
		}
	}
}

?>
