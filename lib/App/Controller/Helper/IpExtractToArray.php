<?php

/**
* @author Tim Rupp
*/
class App_Controller_Helper_IpExtractToArray extends Zend_Controller_Action_Helper_Abstract {
	public function direct($list) {
		require_once(_ABSPATH.'/lib/Ip.php');

		preg_match_all('/('.RE_IP_ADD.')/', $list, $matches);

		if (empty($matches)) {
			return array();
		} else if(empty($matches[0])) {
			return array();
		}

		$data = array_unique(array_filter($matches[0]));

		return $data;
	}
}

?>
