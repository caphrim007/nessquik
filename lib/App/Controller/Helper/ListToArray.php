<?php

/**
* @author Tim Rupp
*/
class App_Controller_Helper_ListToArray extends Zend_Controller_Action_Helper_Abstract {
	public function direct($list) {
		/**
		* I wanted commas, Zingelman wanted newlines, Nordwall
		* made me remember about \r\n. Now, they all become commas
		*
		* From ngkongs@gmail.com's comment to the nl2br function
		* on the PHP site...
		*
		*	windows = \r\n
		*	unix = \n
		*	mac = \r
		*/
		$list = str_replace(array("\r\n","\r","\n"), ",", $list);
		$list = trim($list);
		$data = array();

		if (strpos($list, ',') !== false) {
			$data = explode(',', $list);
		} else {
			$data = array($list);
		}

		$data = array_unique(array_filter($data));

		return $data;
	}
}

?>
