<?php

/**
* @author Tim Rupp
*/
abstract class Automation_Abstract {
	abstract function add($resource);
	abstract function get($resource = null, $page = 1, $limit = 15);
	abstract function exists($resource);
	abstract function delete($id);
}

?>
