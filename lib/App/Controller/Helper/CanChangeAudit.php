<?php

/**
* @author Tim Rupp
*/
class App_Controller_Helper_CanChangeAudit extends Zend_Controller_Action_Helper_Abstract {
	public function direct($accountId, $auditId) {
		$controller = $this->getActionController();
		$account = new Account($accountId);

		$auditAllowed = $account->acl->isAllowed('Audit', $auditId);
		$capabilityAllowed = $controller->session->acl->isAllowed('Capability', array('admin_operator', 'edit_audit'));

		if ($auditAllowed || $capabilityAllowed) {
			return true;
		} else {
			return false;
		}
	}
}

?>
