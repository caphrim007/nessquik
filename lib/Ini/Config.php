<?php

/**
* @author Tim Rupp
*/
class Ini_Config {
	static $instance;

	public static function getInstance($ident = 'production') {
		if (empty(self::$instance[$ident])) {
			$defaultIni = sprintf('%s/etc/default/config.conf', _ABSPATH);
			$localIni = sprintf('%s/etc/local/config.conf', _ABSPATH);

			if (!file_exists($defaultIni)) {
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

			if (isset($default->$ident)) {
				$default->$ident->instance = $ident;
				$ini = $default->$ident;
				self::$instance[$ident] = $ini;
			} else {
				throw new Exception(sprintf('Ident "%s" was not found in the configuration files', $ident));
			}
		}
		return self::$instance[$ident];
	}

	public static function get($ident = 'production') {
		$defaultIni = sprintf('%s/etc/default/config.conf', _ABSPATH);
		$localIni = sprintf('%s/etc/local/config.conf', _ABSPATH);

		if (!file_exists($defaultIni)) {
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

		$default->$ident->instance = $ident;
		return $default->$ident;
	}
}

?>
