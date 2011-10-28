<?php

/**
* @author Tim Rupp
*/
class App_Controller_Helper_IncludeTarget extends Zend_Controller_Action_Helper_Abstract {
	public function direct($account, $audit, $target, $type) {
		try {
			switch($type) {
				case 'hostname':
					if (!$account->acl->isAllowed('HostnameTarget', $target)) {
						throw new Zend_Controller_Action_Exception('You do not have permission to scan this host');
					}
					break;
				case 'ipaddress':
				case 'network':
					if (!$account->acl->isAllowed('NetworkTarget', $target)) {
						throw new Zend_Controller_Action_Exception('You do not have permission to scan this network');
					}
					break;
				case 'range':
					if (!$account->acl->isAllowed('RangeTarget', $target)) {
						throw new Zend_Controller_Action_Exception('You do not have permission to scan this range');
					}
					break;
				case 'cluster':
					if (!$account->acl->isAllowed('ClusterTarget', $target)) {
						throw new Zend_Controller_Action_Exception('You do not have permission to scan this cluster');
					}
					break;
				case 'vhost':
					if (!$account->acl->isAllowed('VhostTarget', $target)) {
						throw new Zend_Controller_Action_Exception('You do not have permission to scan this vhost');
					}
					break;
				default:
					throw new Zend_Controller_Action_Exception('Unknown target type specified');
			}

			if ($type == 'ipaddress') {
				/**
				* Special handler here because IP's should be checked for
				* permission as a 'network' type, but created in the
				* audit file as a 'hostname' type'
				*/
				$type = 'hostname';
			}

			return $audit->includeTarget($target, $type);
		} catch (Exception $error) {
			return false;
		}
	}
}

?>
