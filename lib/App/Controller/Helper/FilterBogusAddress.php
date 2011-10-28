<?php

/**
* @author Tim Rupp
*/
class App_Controller_Helper_FilterBogusAddress extends Zend_Controller_Action_Helper_Abstract {
	public function direct($address) {
		return array_filter($address, array($this,'_filterBogusAddress')); 
	}

	protected function _filterBogusAddress($address) {
		if ($address == 'email address') {
			return false;
		} else if ($address == 'messenger name') {
			return false;
		} else if (empty($address)) {
			return false;
		} else if (strpos($address, '@') === false) {
			return false;
		} else {
			return true;
		}
	}
}

?>
