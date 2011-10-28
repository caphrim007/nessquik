<?php

/**
* Check to see if Nessus is running
*
* If Nessus is not running, obviously there could be a problem
* because no scheduled scans would be run. This will try to determine
* if the server is running on the local host. If nessquik is configured
* so that the scanner is on a different host from nessquik, then the
* check will always return true because there is no good way to
* absolutely make sure it is running on a remote system
*
* @author Tim Rupp
*/
class Maintenance_Plugin_CheckNessus extends Maintenance_Plugin_Abstract {
	const IDENT = __CLASS__;

	public function dispatch(Maintenance_Request_Abstract $request) {
		$log = App_Log::getInstance(self::IDENT);
		$config = Ini_Config::getInstance();

		return;

		$localhost = array(
			'localhost',
			'127.0.0.1'
		);

		if (isset($_SERVER['SERVER_NAME'])) {
			$localhost[] = $_SERVER['SERVER_NAME'];
		}

		if (isset($_SERVER['SERVER_ADDR'])) {
			$localhost[] = $_SERVER['SERVER_ADDR'];
		}

		/**
		* If the nessus server is not running on localhost,
		* there is no good (said fast) way to know if it is running.
		* Therefore always return success if not running on localhost
		*/
		if (array_search($config->nessus->server, $localhost) === false) {
			$log->log("Nessus not running on localhost. Skipping check", Zend_Log::INFO);
			return true;
		} else {
			$log->log("Checking to see if Nessus is running", Zend_Log::INFO);
			exec("/bin/ps auxw|grep nessusd|grep -v grep", $pso);
			$pso    = @preg_replace("/\s+/", " ", $pso[0]);
			$list   = explode(" ", $pso);
			$pid    = @$list[1];
			$start  = @$list[8];

			if ($pid == "") {
				$log->info("Nessus is not running");
				return false;
			} else {
				$log->info("Nessus is running");
				return true;
			}
		}
	}
}

?>
