<?php

/**
* @author Tim Rupp
*/
class Permissions {
	const IDENT = __CLASS__;

	protected $permissions;

	public function __construct($type = null) {
		$permissions = array();

		if ($type !== null) {
			$class = 'Permissions_'.$type;
			$permission = new $class;
			if ($permission instanceof Permissions_Abstract) {
				$this->permissions[$type] = $permission;
			}
		}
	}

	/**
	* @throws Permissions_Exception
	*/
	public function add($type, $resource) {
		if (isset($this->permissions[$type])) {
			$permission = $this->permissions[$type];
		} else {
			$class = 'Permissions_'.$type;
			$permission = new $class;
		}

		if ($permission instanceof Permissions_Abstract) {
			return $permission->add($resource);
		} else {
			throw new Permissions_Exception('The supplied permission type is invalid');
		}
	}

	/**
	* @throws Permissions_Exception
	*/
	public function delete($type, $resource = null) {
		if (isset($this->permissions[$type])) {
			$permission = $this->permissions[$type];
		} else {
			$class = 'Permissions_'.$type;
			$permission = new $class;
		}

		if ($permission instanceof Permissions_Abstract) {
			return $permission->delete($resource);
		} else {
			throw new Permissions_Exception('The supplied permission type is invalid');
		}
	}

	/**
	* @throws Permissions_Exception
	*/
	public function exists($type, $resource) {
		if (isset($this->permissions[$type])) {
			$permission = $this->permissions[$type];
		} else {
			$class = 'Permissions_'.$type;
			$permission = new $class;
		}

		if ($permission instanceof Permissions_Abstract) {
			return $permission->exists($resource);
		} else {
			throw new Permissions_Exception('The supplied permission type is invalid');
		}
	}

	/**
	* @throws Permissions_Exception
	*/
	public function get($type, $resource = null, $page = 1, $limit = 15) {
		if (isset($this->permissions[$type])) {
			$permission = $this->permissions[$type];
		} else {
			$class = 'Permissions_'.$type;
			$permission = new $class;
		}

		if ($permission instanceof Permissions_Abstract) {
			return $permission->get($resource, $page, $limit);
		} else {
			throw new Permissions_Exception('The supplied permission type is invalid');
		}
	}

	/**
	* @throws Permissions_Exception
	*/
	public function getPattern($type, $pattern = null, $resource = null, $page = 1, $limit = 15) {
		if (isset($this->permissions[$type])) {
			$permission = $this->permissions[$type];
		} else {
			$class = 'Permissions_'.$type;
			$permission = new $class;
		}

		if ($permission instanceof Permissions_Abstract) {
			return $permission->getPattern($resource, $pattern, $page, $limit);
		} else {
			throw new Permissions_Exception('The supplied permission type is invalid');
		}
	}

	/**
	* @throws Permissions_Exception
	*/
	public function getId($type, $resource) {
		if (isset($this->permissions[$type])) {
			$permission = $this->permissions[$type];
		} else {
			$class = 'Permissions_'.$type;
			$permission = new $class;
		}

		if ($permission instanceof Permissions_Abstract) {
			return $permission->getId($resource);
		} else {
			throw new Permissions_Exception('The supplied permission type is invalid');
		}
	}
}

?>
