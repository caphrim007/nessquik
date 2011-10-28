<?php

/**
* @author Tim Rupp
*/
abstract class Regex_Abstract {
	protected $regexId;

	public function __construct($regexId) {
		if (is_numeric($regexId)) {
			$this->regexId = $regexId;
		} else {
			throw new Regex_Exception('The supplied Regex ID is invalid');
		}
	}

	abstract function get($page = 1, $limit = 15);
	abstract function getIds();
}

?>
