<?php

/**
* @author Tim Rupp
*/
abstract class Permissions_Abstract {
	abstract function add($resource);
	abstract function get($resource = null, $page = 1, $limit = 15);
	abstract function getPattern($resource = null, $pattern = null, $page = 1, $limit = 15);
	abstract function getId($target);
	abstract function exists($resource);
	abstract function delete($id);
}

?>
