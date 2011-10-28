<?php

/**
* Singleton class for returning a general logging
* object.
*
* @author Tim Rupp
*/
class App_Log {
	private static $instance;

	const EMERG	= Zend_Log::EMERG;
	const ALERT 	= Zend_Log::ALERT;
	const CRIT 	= Zend_Log::CRIT;
	const ERR	= Zend_Log::ERR;
	const WARN 	= Zend_Log::WARN;
	const NOTICE 	= Zend_Log::NOTICE;
	const INFO 	= Zend_Log::INFO;
	const DEBUG 	= Zend_Log::DEBUG;

	public static function getInstance($ident = 'App', $logFile = null) {
		$config	= Ini_Config::getInstance();
		$bFilter = new Zend_Filter_Boolean(Zend_Filter_Boolean::ALL);

		// Allows same ident to be used in multiple logs
		$hash = md5($ident.$logFile);

		if (empty(self::$instance[$hash])) {
			$mask = strtolower($config->debug->log->mask);

			if (is_null($logFile)) {
				$logFile = $config->debug->log->messages;
			}

			switch($mask) {
				case "err":
				case "error":
					$filter = new Zend_Log_Filter_Priority(App_Log::ERR);
					break;
				case "warn":
				case "warning":
					$filter = new Zend_Log_Filter_Priority(App_Log::WARN);
					break;
				case "info":
					$filter = new Zend_Log_Filter_Priority(App_Log::INFO);
					break;
				case "debug":
				case "all":
					$filter = new Zend_Log_Filter_Priority(App_Log::DEBUG);
					break;
			}

			$log = new Zend_Log();
			$log->addFilter($filter);

			$writer = new Zend_Log_Writer_Null();
			$log->addWriter($writer);

			if (is_writeable(dirname($logFile)) && (file_exists($logFile) && is_writable($logFile))) {
				$localMessages = true;
			} else if (is_writeable(dirname($logFile)) && !file_exists($logFile)) {
				$localMessages = true;
			} else if (file_exists($logFile) && is_writable($logFile)) {
				$localMessages = true;
			} else {
				$localMessages = false;
			}

			if ($localMessages === true) {
				$writer = new Zend_Log_Writer_Stream($logFile);
				$formatter = new App_Log_Formatter_Default($ident);
				$writer->setFormatter($formatter);
				$log->addWriter($writer);
			}

			if ($bFilter->filter($config->debug->log->firebug)) {
				$writer = new Zend_Log_Writer_Firebug();
				$log->addWriter($writer);
			}

			if ($bFilter->filter($config->debug->log->stderr)) {
				$writer = new Zend_Log_Writer_Stream('php://stderr');
				$log->addWriter($writer);
			}

			self::$instance[$hash] = $log;
		}
		
		return self::$instance[$hash];
	}
}

?>
