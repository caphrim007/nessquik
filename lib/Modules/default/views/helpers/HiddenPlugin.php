<?php

/**
* @author Tim Rupp
*/
class App_View_Helper_HiddenPlugin extends Zend_View_Helper_Abstract {
	public function hiddenPlugin($plugin) {
		$hidden = array(14274,11219,10335,14272,34220,10180);

		if (in_array($plugin, $hidden)) {
			return true;
		} else {
			return false;
		}
	}
}

?>
