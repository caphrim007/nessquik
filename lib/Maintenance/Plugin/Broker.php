<?php

/**
* This code borrows heavily from the Plugin Broker provided by
* the Zend Framework
*
* @author Tim Rupp
*/
class Maintenance_Plugin_Broker extends Maintenance_Plugin_Abstract {
	/**
	* Array of instance of objects extending Maintenance_Plugin_Abstract
	*
	* @var array
	*/
	protected $_plugins = array();

	protected $_params = array();

	const IDENT = __CLASS__;

	public function setParams($params) {
		if (is_array($params)) {
			$this->_params = $params;
		}
	}

	public function getParams() {
		return $this->_params;
	}

	/**
	* Register a plugin.
	*
	* @param  Maintenance_Plugin_Abstract $plugin
	* @param  int $stackIndex
	* @return Maintenance_Plugin_Broker
	*/
	public function registerPlugin(Maintenance_Plugin_Abstract $plugin, $stackIndex = null) {
		if (false !== array_search($plugin, $this->_plugins, true)) {
			throw new Maintenance_Exception('Plugin already registered');
		}

		$stackIndex = (int) $stackIndex;

		if ($stackIndex) {
			if (isset($this->_plugins[$stackIndex])) {
				throw new Maintenance_Exception('Plugin with stackIndex "' . $stackIndex . '" already registered');
			}

			$this->_plugins[$stackIndex] = $plugin;
		} else {
			$stackIndex = count($this->_plugins);
			while (isset($this->_plugins[$stackIndex])) {
				++$stackIndex;
			}

			$this->_plugins[$stackIndex] = $plugin;
		}

		ksort($this->_plugins);

		return $this;
	}

	/**
	* Unregister a plugin.
	*
	* @param string|Maintenance_Plugin_Abstract $plugin Plugin object or class name
	* @return Maintenance_Plugin_Broker
	*/
	public function unregisterPlugin($plugin) {
		if ($plugin instanceof Maintenance_Plugin_Abstract) {
			// Given a plugin object, find it in the array
			$key = array_search($plugin, $this->_plugins, true);

			if (false === $key) {
				throw new Maintenance_Exception('Plugin never registered.');
			}

			unset($this->_plugins[$key]);
		} elseif (is_string($plugin)) {
			// Given a plugin class, find all plugins of that class and unset them
			foreach ($this->_plugins as $key => $_plugin) {
				$type = get_class($_plugin);
				if ($plugin == $type) {
					unset($this->_plugins[$key]);
				}
			}
		}

		return $this;
	}

	/**
	* Is a plugin of a particular class registered?
	*
	* @param  string $class
	* @return bool
	*/
	public function hasPlugin($class) {
		foreach ($this->_plugins as $plugin) {
			$type = get_class($plugin);

			if ($class == $type) {
				return true;
			}
		}

		return false;
	}

	/**
	* Retrieve a plugin or plugins by class
	*
	* @param  string $class Class name of plugin(s) desired
	* @return false|Maintenance_Plugin_Abstract|array
	*/
	public function getPlugin($class) {
		$found = array();
		foreach ($this->_plugins as $plugin) {
			$type = get_class($plugin);
			if ($class == $type) {
				$found[] = $plugin;
			}
		}

		switch (count($found)) {
			case 0:
				return false;
			case 1:
				return $found[0];
			default:
				return $found;
		}
	}

	/**
	* Retrieve all plugins
	*
	* @return array
	*/
	public function getPlugins() {
		return $this->_plugins;
	}

	/**
	* Called before Maintenance_Engine begins evaluating the
	* request against its routes.
	*
	* @param Maintenance_Request_Abstract $request
	* @return void
	*/
	public function maintenanceStartup(Maintenance_Request_Abstract $request) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::factory($config->database->default);
		$date = new Zend_Date;

		$params = $this->getParams();

		foreach ($this->_plugins as $plugin) {
			$class = get_class($plugin);

			try {
				$data = array(
					'last_run' => $date->get(Zend_Date::W3C)
				);

				$where = $db->quoteInto('task = ?', $class);

				$db->update('maintenance_state', $data, $where);
				$plugin->maintenanceStartup($request);
			} catch (Exception $error) {
				$log->err($error->getMessage());
				throw new Maintenance_Plugin_Exception($error->getMessage());
			}
		}
	}

	/**
	* Called before Maintenance_Engine exits its iterations over
	* the route set.
	*
	* @param  Maintencance_Request_Abstract $request
	* @return void
	*/
	public function maintenanceShutdown(Maintenance_Request_Abstract $request) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::factory($config->database->default);
		$date = new Zend_Date;

		$params = $this->getParams();

		foreach ($this->_plugins as $plugin) {
			$class = get_class($plugin);

			try {
				$data = array(
					'last_finish' => $date->get(Zend_Date::W3C)
				);

				$where = $db->quoteInto('task = ?', $class);

				$db->update('maintenance_state', $data, $where);
				$plugin->maintenanceShutdown($request);
			} catch (Exception $error) {
				$log->err($error->getMessage());
				throw new Maintenance_Plugin_Exception($error->getMessage());
			}
		}
	}

	/**
	* @param  Maintenace_Request_Abstract $request
	* @return void
	*/
	public function dispatch(Maintenance_Request_Abstract $request) {
		foreach ($this->_plugins as $plugin) {
			$plugin->dispatch($request);
		}
	}
}

?>
