<?php

/**
* @author Tim Rupp
*/
class App_Controller_Helper_InterpretStatus extends Zend_Controller_Action_Helper_Abstract {
	public function direct($status, $short = false) {
		$status = strtolower(trim($status));
		$filter = new Zend_Filter_Boolean(Zend_Filter_Boolean::ALL);
		$short = $filter->filter($short);

		switch($status) {
			case 'r':
			case 'running':
				if ($short === true) {
					return 'R';
				} else {
					return 'running';
				}
			case 'parked':
			case 'n':
			case 'not_running':
				if ($short === true) {
					return 'N';
				} else {
					return 'parked';
				}
			case 'p':
			case 'pending':
				if ($short === true) {
					return 'P';
				} else {
					return 'pending';
				}
			case 'f':
			case 'finished':
				if ($short === true) {
					return 'F';
				} else {
					return 'finished';
				}
			default:
				return null;
		}
	}
}

?>
