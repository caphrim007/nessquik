<?php

/**
* @author Tim Rupp
*/
class App_Cache {
	protected $cache = null;

	const IDENT = __CLASS__;

	public function __construct($type) {
		$config	= Ini_Cache::getInstance();
		$log = App_Log::getInstance(self::IDENT);

		try {
			switch($type) {
				case 'memcache':
					$cache = Zend_Cache::factory(
						'Core',
						'Memcached',
						$config->frontend->toArray(),
						$config->memcache->toArray()
					);
					$this->cache = $cache;
					break;
				case 'translate':
					if (!is_writeable($config->translate->cache_dir)) {
						throw new App_Exception('Unable to write to the cache directory! Check the directory permissions.');
					}

					$cache = Zend_Cache::factory('Core',
						$config->translate->backend,
						$config->frontend->toArray(),
						$config->translate->toArray()
					);
					$this->cache = $cache;
					break;
				default:
					throw new App_Exception('Specified cache backed does not exist');
					break;
			}
		} catch (Exception $error) {
			$log->err($error->getMessage());
			throw new App_Exception($error->getMessage());
		}
	}

	public function __call($method, $args) {
		if ($this->cache === null) {
			return false;
		} else {
			return call_user_func_array(array($this->cache, $method), $args);
		}
	}

	public function getCache() {
		return $this->cache;
	}
}

?>
