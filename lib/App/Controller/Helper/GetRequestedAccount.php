<?php

/**
* @author Tim Rupp
*/
class App_Controller_Helper_GetRequestedAccount extends Zend_Controller_Action_Helper_Abstract {
	public function direct() {
		$controller = $this->getActionController();
		$request = $controller->getRequest();
		$accountId = $request->getParam('accountId');

		if (empty($accountId)) {
			$account = $controller->session;
		} else if ($accountId == $controller->session->id) {
			// User supplied account ID; can only edit the audit if session matches...
			$account = new Account($accountId);
		} elseif ($controller->session->acl->isAllowed('Capability', 'admin_operator')) {
			// ...or if the accessing user has admin privileges
			$account = new Account($accountId);
		} else {
			$redirector = $controller->getHelper('Redirector');
			$redirector->gotoSimple('permission-denied', 'error', 'default');
		}

		return $account;
	}
}

?>
