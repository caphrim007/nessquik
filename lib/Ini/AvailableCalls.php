<?php

/**
*
*/
class Ini_AvailableCalls {
	static $instance;

	public static function getInstance() {
		if (empty(self::$instance)) {
			$defaultIni = sprintf('%s/etc/default/calls.conf', _ABSPATH);
			$localIni = sprintf('%s/etc/local/calls.conf', _ABSPATH);

			if (!file_exists($defaultIni)) {
				throw new App_Exception('Default AvailableCalls Ini file was not found');
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

			self::$instance = $default;
		}
		return self::$instance;
	}
}

?>
