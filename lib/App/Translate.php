<?php

/**
* @author Tim Rupp
*/
class App_Translate {
	private static $instance;

	static function getInstance() {
		if (empty(self::$instance)) {
			$config = Ini_Config::getInstance();

			$translate = new Zend_Translate('gettext',
				sprintf(_ABSPATH.'/usr/share/locale/%s/LC_MESSAGES/messages.mo', $config->misc->locale),
				$config->misc->locale
			);

			self::$instance = $translate;
		}

		return self::$instance;
	}
}

?>
