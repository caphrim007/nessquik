<?php

/**
* @author Tim Rupp
*/
class App_Controller_Helper_DetermineTargetType extends Zend_Controller_Action_Helper_Abstract {
	public function direct($target) {
		try {
			Zend_Loader::loadclass('Fnal_Miscomp');
			$hasClusters = true;
		} catch (Exception $error) {
			$hasClusters = false;
		}

		if (Ip::isCidr($target)) {
			return 'network';
		} else if(Ip::isIpAddress($target)) {
			return 'hostname';
		} else if (Ip::isRange($target)) {
			return 'range';
		} else if (Ip::isVhost($target)) {
			return 'vhost';
		} else if ($hasClusters === true) {
			if (Fnal_Miscomp::isCluster($target)) {
				return 'cluster';
			}
		}

		return 'hostname';
	}
}

?>
