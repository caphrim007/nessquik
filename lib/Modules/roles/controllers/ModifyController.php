<?php

/**
* @author Tim Rupp
*/
class Roles_ModifyController extends Zend_Controller_Action {
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

		if (!$this->session->acl->isAllowed('Capability', array('admin_operator', 'edit_role'))) {
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

	public function saveAction() {
		$status = false;
		$message = null;
		$addPermissions = array();

		$log = App_Log::getInstance(self::IDENT);

		$request = $this->getRequest();
		$request->setParamSources(array('_POST'));

		$id = $request->getParam('roleId');

		if ($id == '_new') {
			$id = Role_Util::create();
		}

		$role = new Role($id);

		$roleName = $request->getParam('role-name');
		$roleDescription = $request->getParam('role-description');

		$selectedMethods = $request->getParam('selected-api');
		$selectedCapabilities = $request->getParam('selected-capability');
		$selectedNetworkTargets = $request->getParam('selected-networktarget');
		$selectedHostnameTargets = $request->getParam('selected-hostnametarget');
		$selectedScanners = $request->getParam('selected-scanner');
		$selectedAudits = $request->getParam('selected-audit');
		$selectedPolicies = $request->getParam('selected-policy');

		if (!is_array($selectedMethods)) {
			$selectedMethods = array();
		}

		if (!is_array($selectedCapabilities)) {
			$selectedCapabilities = array();
		}

		if (!is_array($selectedNetworkTargets)) {
			$selectedNetworkTargets = array();
		}

		if (!is_array($selectedHostnameTargets)) {
			$selectedHostnameTargets = array();
		}

		if (!is_array($selectedScanners)) {
			$selectedScanners = array();
		}

		if (!is_array($selectedAudits)) {
			$selectedAudits = array();
		}

		if (!is_array($selectedPolicies)) {
			$selectedPolicies = array();
		}

		try {
			if (empty($roleName)) {
				throw new Exception('Role name cannot be empty');
			}

			$role->name = $roleName;
			$role->description = $roleDescription;
			$role->update();

			$addPermissions = array_merge($addPermissions, $selectedMethods);
			$addPermissions = array_merge($addPermissions, $selectedCapabilities);
			$addPermissions = array_merge($addPermissions, $selectedNetworkTargets);
			$addPermissions = array_merge($addPermissions, $selectedHostnameTargets);
			$addPermissions = array_merge($addPermissions, $selectedScanners);
			$addPermissions = array_merge($addPermissions, $selectedAudits);
			$addPermissions = array_merge($addPermissions, $selectedPolicies);

			$addPermissions = array_unique($addPermissions);

			if (!empty($addPermissions)) {
				$role->clearPermissions();
				foreach($addPermissions as $permission) {
					$role->addPermission($permission);
				}
			}

			$status = true;
		} catch (Exception $error) {
			$log->err($error->getMessage());

			$status = false;
			$message = $error->getMessage();
		}

		$this->view->response = array(
			'status' => $status,
			'message' => $message
		);
	}

	public function editAction() {
		$allCapabilities = array();
		$selectedCapabilities = array();
		$allMethods = array();
		$selectedMethods = array();
		$networkTargets = array();
		$selectedNetworkTargets = array();
		$hostnameTargets = array();
		$selectedHostnameTargets = array();
		$clusterTargets = array();
		$selectedClusterTargets = array();
		$scanners = array();
		$selectedScanners = array();
		$isNew = false;
		$selectedAudits = array();
		$selectedPolicies = array();

		$request = $this->getRequest();
		$request->setParamSources(array('_GET'));

		$id = $request->getParam('id');

		if ($id == '_new') {
			$isNew = true;
		} else {
			$role = new Role($id);
			$selectedMethods = $role->get('ApiMethod', 0, 0);
			$selectedCapabilities = $role->get('Capability', 0, 0);
			$selectedNetworkTargets = $role->get('NetworkTarget', 0, 0);
			$selectedHostnameTargets = $role->get('HostnameTarget', 0, 0);
			$selectedScanners = $role->get('Scanner', 0, 0);
			$selectedAudits = $role->get('Audit', 0, 0);
			$selectedPolicies = $role->get('Policy', 0, 0);
		}

		$permissions = new Permissions;

		$methods = $permissions->get('ApiMethod', null, 0, 0);
		$capabilities = $permissions->get('Capability', null, 0, 0);
		$networkTargets = $permissions->get('NetworkTarget', null, 0, 15);
		$hostnameTargets = $permissions->get('HostnameTarget', null, 0, 15);
		$scanners = $permissions->get('Scanner', null, 0, 0);
		$audits = $permissions->get('Audit', null, 0, 15);
		$policies = $permissions->get('Policy', null, 0, 15);

		$this->view->assign(array(
			'allCapabilities' => $capabilities,
			'allMethods' => $methods,
			'hostnameTargets' => $hostnameTargets,
			'id' => $id,
			'role' => $role,
			'isNew' => $isNew,
			'networkTargets' => $networkTargets,
			'scanners' => $scanners,
			'selectedCapabilities' => $selectedCapabilities,
			'selectedHostnameTargets' => $selectedHostnameTargets,
			'selectedMethods' => $selectedMethods,
			'selectedNetworkTargets' => $selectedNetworkTargets,
			'selectedScanners' => $selectedScanners,
			'selectedAudits' => $selectedAudits,
			'selectedPolicies' => $selectedPolicies,
			'policies' => $policies,
			'audits' => $audits
		));
	}

	public function addPermissionAction() {
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

	public function searchPermissionAction() {
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

	public function deletePermissionAction() {
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
