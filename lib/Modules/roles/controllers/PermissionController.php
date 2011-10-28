<?php

/**
* @author Tim Rupp
*/
class Roles_PermissionController extends Zend_Controller_Action {
	public $session;

	const IDENT = __CLASS__;

	public function init() {
		parent::init();

		$config = Ini_Config::getInstance();
		$auth = Zend_Auth::getInstance();
		$request = $this->getRequest();

		$sessionUser = $auth->getIdentity();
		$sessionId = Account_Util::getId($sessionUser);
		$this->session = new Account($sessionId);

		if ($this->session->isFirstBoot()) {
			$this->_redirector = $this->_helper->getHelper('Redirector');
			$this->_redirector->gotoSimple('index', 'index', 'start');
		}

		if (!$this->session->acl->isAllowed('Capability', 'edit_role')) {
			$this->_redirector = $this->_helper->getHelper('Redirector');
			$this->_redirector->gotoSimple('permission-denied', 'error', 'default');
		}

		$this->view->assign(array(
			'action' => $request->getActionName(),
			'config' => $config,
			'controller' => $request->getControllerName(),
			'module' => $request->getModuleName(),
			'session' => $this->session
		));
	}

	public function createAction() {
		$status = false;
		$message = null;

		$log = App_Log::getInstance(self::IDENT);

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));
		$permission = $request->getParam('permission');
		$type = $request->getParam('type');

		$permissions = new Permissions;

		switch($type) {
			case 'capability':
				if ($permissions->exists('Capability', $permission)) {
					$status = false;
					$message = 'The specified capability already exists';
				} else {
					$result = $permissions->add('Capability', $permission);
					if ($result === true) {
						$status = true;
						$message = 'Successfully added the capability';
					} else {
						$status = false;
						$message = 'Failed to add the capability';
					}
				}
				break;
			case 'hostname':
				if ($permissions->exists('HostnameTarget', $permission)) {
					$status = false;
					$message = 'The specified hostname target already exists';
				} else {
					$result = $permissions->add('HostnameTarget', $permission);
					if ($result === true) {
						$status = true;
						$message = 'Successfully added the hostname target';
					} else {
						$status = false;
						$message = 'Failed to add the hostname target';
					}
				}
				break;
			case 'network':
				if (Ip::isIpAddress($permission) || Ip::isRange($permission) || Ip::isCidr($permission)) {
					if ($permissions->exists('NetworkTarget', $permission)) {
						$status = false;
						$message = 'The specified network target already exists';
					} else {
						$result = $permissions->add('NetworkTarget', $permission);
						if ($result === true) {
							$status = true;
							$message = 'Successfully added the network target';
						} else {
							$status = false;
							$message = 'Failed to add the network target';
						}
					}
				} else {
					$status = false;
					$message = 'The specified target is not an IP address, CIDR or range';
				}
				break;
		}

		$this->view->response = array(
			'status' => $status,
			'message' => $message
		);
	}

	public function searchAction() {
		$results = array();
		$limit = 12;

		$request = $this->getRequest();
		$request->setParamSources(array('_GET'));

		$page = $request->getParam('page');
		$type = $request->getParam('type');

		if (empty($page)) {
			$page = 1;
		}

		$permissions = new Permissions;

		switch ($type) {
			case 'capability':
				$results = $permissions->get('Capability', null, $page, $limit);
				break;
			case 'network':
				$results = $permissions->get('NetworkTarget', null, $page, $limit);
				break;
			case 'hostname':
				$results = $permissions->get('HostnameTarget', null, $page, $limit);
				break;
		}

		$this->view->assign(array(
			'limit' => $limit,
			'page' => $page,
			'results' => $results,
			'type' => $type
		));
	}

	public function deleteAction() {
		$status = false;
		$message = null;

		$log = App_Log::getInstance(self::IDENT);

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$permissionId = $request->getParam('permissionId');
		$type = $request->getParam('type');

		$permissions = new Permissions;

		try {
			switch ($type) {
				case 'capability':
					$status = $permissions->delete('Capability', $permissionId);
					break;
				case 'network':
					$status = $permissions->delete('NetworkTarget', $permissionId);
					break;
				case 'hostname':
					$status = $permissions->delete('HostnameTarget', $permissionId);
					break;
				default:
					throw new Zend_Controller_Action_Exception('Unknown permission type specified');
			}
		} catch (Exception $error) {
			$log->err($error->getMessage());
			$status = false;
			$message = $error->getMessage();
		}

		$this->view->response = array(
			'status' => $status,
			'message' => $message,
			'type' => $type,
			'permissionId' => $permissionId
		);
	}
}

?>
