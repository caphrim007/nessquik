<?php

/**
* @author Tim Rupp
*/
abstract class Role_Abstract {
	protected $roleId;

	public function __construct($roleId) {
		if (is_numeric($roleId)) {
			$this->roleId = $roleId;
		} else {
			throw new Role_Exception('The supplied Role ID is invalid');
		}
	}

	abstract function get($page = 1, $limit = 15);
	abstract function getIds();
}

?>
