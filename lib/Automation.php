<?php

/**
* @author Tim Rupp
*/
class Automation {
	const IDENT = __CLASS__;

	protected $_automations;

	public function __construct($type = null) {
		$this->_automations = array();

		if ($type !== null) {
			$class = 'Automation_'.$type;
			$automation = new $class;
			if ($automation instanceof Automation_Abstract) {
				$this->_automations[$type] = $automation;
			}
		}
	}

	/**
	* @throws Automation_Exception
	*/
	public function add($type, $resource) {
		if (isset($this->_automations[$type])) {
			$automation = $this->_automations[$type];
		} else {
			$class = 'Automation_'.$type;
			$automation = new $class;
		}

		if ($automation instanceof Automation_Abstract) {
			return $automation->add($resource);
		} else {
			throw new Automation_Exception('The supplied automation type is invalid');
		}
	}

	/**
	* @throws Automation_Exception
	*/
	public function delete($type, $resource = null) {
		if (isset($this->_automations[$type])) {
			$automation = $this->_automations[$type];
		} else {
			$class = 'Automation_'.$type;
			$automation = new $class;
		}

		if ($automation instanceof Automation_Abstract) {
			return $automation->delete($resource);
		} else {
			throw new Automation_Exception('The supplied automation type is invalid');
		}
	}

	/**
	* @throws Automation_Exception
	*/
	public function exists($type, $resource) {
		if (isset($this->_automations[$type])) {
			$automation = $this->_automations[$type];
		} else {
			$class = 'Automation_'.$type;
			$automation = new $class;
		}

		if ($automation instanceof Automation_Abstract) {
			return $automation->exists($resource);
		} else {
			throw new Automation_Exception('The supplied automation type is invalid');
		}
	}

	/**
	* @throws Automation_Exception
	*/
	public function get($type, $resource = null, $page = 1, $limit = 15) {
		if (isset($this->_automations[$type])) {
			$automation = $this->_automations[$type];
		} else {
			$class = 'Automation_'.$type;
			$automation = new $class;
		}

		if ($automation instanceof Automation_Abstract) {
			return $automation->get($resource, $page, $limit);
		} else {
			throw new Automation_Exception('The supplied automation type is invalid');
		}
	}
}

?>
