<?php

/**
* @author Tim Rupp
*/
class App_Controller_Helper_GetAuditServerCookie extends Zend_Controller_Action_Helper_Abstract {
	public function direct($scannerId, $username, $password) {
		$scanner = new Audit_Server($scannerId);
		$scannerLog = App_Log::getInstance(get_class($scanner));
		$scanner->adapter->setLogger($scannerLog);

		$cookie = $scanner->adapter->login($username, $password);

		if ($cookie instanceof Zend_Http_Cookie) {
			return $cookie;
		} else {
			$cookie = Zend_Http_Cookie::fromString($cookie);
			return $cookie;
		}
	}
}

?>
