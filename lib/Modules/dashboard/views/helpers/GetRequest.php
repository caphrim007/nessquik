<?php

/**
* @author Tim Rupp
*/
class App_View_Helper_GetRequest extends Zend_View_Helper_Abstract {
	public function getRequest() {
		return Zend_Controller_Front::getInstance()->getRequest();
	}
}

?>
