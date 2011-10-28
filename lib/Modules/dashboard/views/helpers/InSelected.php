<?php

/**
* @author Tim Rupp
*/
class App_View_Helper_InSelected extends Zend_View_Helper_Abstract {
	public function inSelected($needle, $haystack) {
		foreach($haystack as $key => $val) {
			$test = false;

			if (isset($val['resource'])) {
				$test = $val['resource'];
			} else if (isset($val['role_id'])) {
				$test = $val['role_id'];
			}

			if ($needle == $test) {
				return true;
			}
		}

		return false;
	}
}

?>
