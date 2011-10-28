<?php

/**
* @author Tim Rupp
*/
abstract class Account_Acl_Abstract {
	protected $accountId;
	protected $_config;
	protected $_db;
	protected $_log;

	public function __construct($accountId) {
		$this->accountId = $accountId;
	}

	abstract function isAllowed($resource);
	abstract function get();
}

?>
