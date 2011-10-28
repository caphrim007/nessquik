<?php

/**
* @author Tim Rupp
*/
class App_Controller_Helper_CanChangePolicy extends Zend_Controller_Action_Helper_Abstract {
	public function direct($accountId, $policyId) {
		$controller = $this->getActionController();
		$account = new Account($accountId);

		$policyAllowed = $account->acl->isAllowed('Policy', $policyId);
		$capabilityAllowed = $controller->session->acl->isAllowed('Capability', array('admin_operator', 'edit_policy'));

		if ($policyAllowed || $capabilityAllowed) {
			return true;
		} else {
			return false;
		}
	}
}

?>
