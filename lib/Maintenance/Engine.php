<?php

/**
* Portions of this classes' code was borrowed from the Front Controller
* class packaged with the Zend Framework
*
* @author Tim Rupp
*/
class Maintenance_Engine {
	const IDENT = __CLASS__;

	/**
	* Singleton instance
	*
	* Marked only as protected to allow extension of the class. To extend,
	* simply override {@link getInstance()}.
	*
	* @var Maintenance_Engine
	*/
	protected static $_instance = null;

	/**
	* Instance of Maintenance_Plugin_Broker
	*
	* @var Maintenance_Plugin_Broker
	*/
	protected $_plugins = null;

	/**
	* Instance of Maintenance_Request_Abstract
	*
	* @var Maintenance_Request_Abstract
	*/
	protected $_request = null;

	/**
	* Consider Cron policies
	*/
	protected $_considerCron = true;

	protected $_params = array();

	/**
	* Constructor
	*
	* Instantiates the plugin broker.
	*
	* @return void
	*/
	public function __construct() {
		$this->_plugins = new Maintenance_Plugin_Broker();
	}

	/**
	* Enforce singleton; disallow cloning 
	* 
	* @return void
	*/
	private function __clone() {

	}

	public function setParams($params) {
		if (is_array($params)) {
			$this->_params = $params;
			$this->_plugins->setParams($params);
		}
	}

	public function getParams() {
		return $this->_params;
	}

	public function considerCron($consider = false) {
		if (($consider === true) || ($consider == 1) || ($consider == 'true')) {
			$this->_considerCron = true;
		} else {
			$this->_considerCron = false;
		}
	}

	/**
	* Singleton instance
	*
	* @return Maintenance_Engine
	*/
	public static function getInstance() {
		if (null === self::$_instance) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	* Register a plugin.
	*
	* @param  Maintenance_Plugin_Abstract $plugin
	* @param  int $stackIndex Optional; stack index for plugin
	* @return Maintenance_Engine
	*/
	public function registerPlugin(Maintenance_Plugin_Abstract $plugin, $stackIndex = null) {
		$this->_plugins->registerPlugin($plugin, $stackIndex);
		return $this;
	}

	/**
	* Unregister a plugin.
	*
	* @param  string|Maintenance_Plugin_Abstract $plugin Plugin class or object to unregister
	* @return Maintenance_Engine
	*/
	public function unregisterPlugin($plugin) {
		$this->_plugins->unregisterPlugin($plugin);
		return $this;
	}

	/**
	* Is a particular plugin registered?
	*
	* @param  string $class
	* @return bool
	*/
	public function hasPlugin($class) {
		return $this->_plugins->hasPlugin($class);
	}

	/**
	* Retrieve a plugin or plugins by class
	*
	* @param  string $class
	* @return false|Maintenance_Plugin_Abstract|array
	*/
	public function getPlugin($class) {
		return $this->_plugins->getPlugin($class);
	}

	/**
	* Retrieve all plugins
	*
	* @return array
	*/
	public function getPlugins() {
		return $this->_plugins->getPlugins();
	}

	/**
	* Retrieve all plugin names
	*
	* @return array
	*/
	public function getPluginNames() {
		$result = array();

		$plugins = $this->_plugins->getPlugins();

		foreach($plugins as $plugin) {
			$result[] = get_class($plugin);
		}

		return $result;
	}

	/**
	* Dispatch a maintenance.
	*
	* @param Maintenance_Request_Abstract|null $request
	* @param Maintenance_Response_Abstract|null $response
	* @return void
	*/
	public function dispatch() {
		$log = App_Log::getInstance(self::IDENT);
		$config = Ini_MaintenancePlugin::getInstance();

		if ($this->_considerCron === true) {
			$log->debug('Asked to consider cron');
			$cron = new Cron('* * * * *');

			foreach($this->getPlugins() as $plugin) {
				$class = get_class($plugin);
				@$schedule = $config->schedule->$class;

				$cron->setPlugin($class);
				$cron->setSchedule($schedule);
				if (!$cron->schedule()) {
					$log->debug(sprintf('Cron schedule not defined for plugin %s', $class));
					$this->unregisterPlugin($class);
				} else {
					$log->debug(sprintf('Maintenance plugin %s will be scheduled to run', $class));
				}
			}
		}

		$log->debug(sprintf('Running the following plugins %s', implode(' ', $this->getPluginNames())));

		$this->_request = new Maintenance_Request_Simple;

		/**
		* Notify plugins of maintenance startup
		*/
		$log->debug('Notifying tasks of maintenance startup');
		$this->_plugins->maintenanceStartup($this->_request);

		$this->_request->clearMessages();

		try {
			$log->debug('Dispatching request to the task');
			$this->_plugins->dispatch($this->_request);
		} catch (Exception $error) {
			$log->err($error->getMessage());
			echo sprintf("%s\n", $error->getMessage());
		}

		$this->_request->clearMessages();

		/**
		* Notify tasks of maintenanceshutdown
		*/
		$log->debug('Notifying tasks of maintenance shutdown');
		$this->_plugins->maintenanceShutdown($this->_request);

		$this->_request->clearMessages();
		unset($this->_request);
		unset($this->_plugins);
	}
}

?>
