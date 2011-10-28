<?php

/**
* @author Tim Rupp
*/
class Ini_Authentication {
	static $instance;

	const IDENT = __CLASS__;

	public static function getInstance() {
		if (empty(self::$instance)) {
			$config = Ini_Config::getInstance();
			$instance = $config->instance;

			$defaultIni = sprintf('%s/etc/default/authentication.conf', _ABSPATH);
			$localIni = sprintf('%s/etc/local/authentication.conf', _ABSPATH);

			if (!file_exists($defaultIni)) {
				$log = App_Log::getInstance(self::IDENT);
				$log->info('Default Authentication Ini file was not found. Using empty class');
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

			self::$instance = $default->$instance;
		}
		return self::$instance;
	}
}

?>
