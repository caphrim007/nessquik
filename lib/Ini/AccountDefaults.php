<?php

/**
* @author Tim Rupp
*/
class Ini_AccountDefaults {
	static $instance;

	public static function getInstance($ident = 'production') {
		if (empty(self::$instance[$ident])) {
			$defaultIni = sprintf('%s/etc/default/account-defaults.conf', _ABSPATH);
			$localIni = sprintf('%s/etc/local/account-defaults.conf', _ABSPATH);

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

			@$default->$ident->instance = $ident;
			$ini = $default->$ident;
			self::$instance[$ident] = $ini;
		}
		return self::$instance[$ident];
	}
}

?>
