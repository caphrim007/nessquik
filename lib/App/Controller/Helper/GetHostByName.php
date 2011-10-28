<?php

/**
* @author Tim Rupp
*/
class App_Controller_Helper_GetHostByName extends Zend_Controller_Action_Helper_Abstract {
	public function direct($host, $timeout = 3) {
		$timeout = escapeshellarg($timeout);
		$host = escapeshellarg($host);

		$query = sprintf('/usr/bin/nslookup -timeout=%s -retry=1 %s', $timeout, $host);
		$output = shell_exec($query);

		if(preg_match('/\nAddress: (.*)\n/', $output, $matches)) {
			return trim($matches[1]);
		}

		return $host;
	}
}

?>
