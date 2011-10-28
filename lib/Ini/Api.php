<?php

/**
* @author Tim Rupp
*/
class Ini_Api {
	static $instance;

	const IDENT = __CLASS__;

	public static function getInstance() {
		if (empty(self::$instance)) {
			$config = Ini_Config::getInstance();
			$instance = $config->instance;

			$defaultIni = sprintf('%s/etc/default/api.conf', _ABSPATH);
			$localIni = sprintf('%s/etc/local/api.conf', _ABSPATH);

			if (!file_exists($defaultIni)) {
				$log = App_Log::getInstance(self::IDENT);
				$log->debug('Default API Ini file was not found. Using empty class');
				$default = new Zend_Config(array(),true);
			} else {
				$default = new Zend_Config_Ini(
					$defaultIni,
					null,
					array('allowModifications' => true)
				);
			}

			if (file_exists($localIni)) {
				$local = new Zend_Config_Ini(
					$localIni,
					null,
					array('allowModifications' => true)
				);

				$default->merge($local);
			}

			$ini = $default->$instance;

			self::$instance = $ini;
		}
		return self::$instance;
	}
}

?>
