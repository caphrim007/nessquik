<?php

/**
* @author Tim Rupp
*/
class App_Controller_Helper_CanAuditTarget extends Zend_Controller_Action_Helper_Abstract {
	public function direct($account, $target, $type) {
		switch($type) {
			case 'HostnameTarget':
				if (!$account->acl->isAllowed('HostnameTarget', $target)) {
					return false;
				}
				break;
			case 'NetworkTarget':
				if (!$account->acl->isAllowed('NetworkTarget', $target)) {
					return false;
				}
				break;
			case 'RangeTarget':
				if (!$account->acl->isAllowed('RangeTarget', $target)) {
					return false;
				}
				break;
			case 'ClusterTarget':
				if (!$account->acl->isAllowed('ClusterTarget', $target)) {
					return false;
				}
				break;
			default:
				return false;
		}

		return true;
	}
}

?>
