<?php

/**
* @author Tim Rupp
*/
class Maintenance_Plugin_CheckSELinux extends Maintenance_Plugin_Abstract {
	const IDENT = __CLASS__;

	public function dispatch(Maintenance_Request_Abstract $request) {
		$log = App_Log::getInstance(self::IDENT);
		$selinux = '/etc/selinux/config';

		if (file_exists($selinux)) {
			$log->info("Checking for SELinux");

			$lines = file($selinux);
			foreach ($lines as $key => $line) {
				$parts		= array();

				if(preg_match('/^(SELINUX=enforcing)/i', $line, $parts) > 0 ) {
					$log->warn("SELinux is enabled, please disable or code may die mysteriously");
					return true;
				}
			}

			return false;
		} else {
			return false;
		}
	}
}

?>
