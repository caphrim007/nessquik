<?php

/**
* @author Tim Rupp
*/
class Account {
	const IDENT = __CLASS__;

	public $acl;
	public $audit;
	public $policy;
	public $role;
	public $scanner;
	public $settings;
	public $contacts;

	protected $_data;
	protected $_id;
	protected $_roles;

	public function __construct($id) {
		if (!is_numeric($id)) {
			throw new Exception('Account ID must be a number');
		} else {
			$this->_id = $id;
		}

		try {
			$this->_roles = $this->getRoles();

			$this->loadAccountData();
		} catch (Exception $error) {
			throw new Audit_Exception($error->getMessage());
		}

		$this->acl = new Account_Acl($id, $this->primary_role);
		$this->audit = new Account_Audit($id);
		$this->policy = new Account_Policy($id);
		$this->role = new Account_Role($id);
		$this->scanner = new Account_Scanner($id);
		$this->settings = new Account_Settings($id);
		$this->contacts = new Account_Contacts($id);
	}

	public function __set($key, $val) {
		switch($key) {
			case 'id':
				return false;
			case 'primary_role':
				if (!in_array($val, $this->_roles)) {
					return false;
				}
				break;
		}

		$this->_data[$key] = $val;
	}

	public function __get($key) {
		switch($key) {
			case 'id':
				return $this->_id;
			case 'serverUsername':
			case 'serverPassword':
			default:
				break;
		}

		if (isset($this->_data[$key])) {
			return $this->_data[$key];
		} else {
			return false;
		}
	}

	public function loadAccountData() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('accounts')
			->where('id = ?', $this->id);

		$log->debug($sql->__toString());
		$stmt = $sql->query();
		$result = $stmt->fetchAll();
		if (empty($result)) {
			throw new Exception('The specified account ID was not found');
		} else {
			foreach($result[0] as $key => $val) {
				$this->$key = $val;
			}
		}

		return true;
	}

	public function createAccountMapping($mapping) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);
		$date = new Zend_Date;

		$mapping = trim($mapping);

		$data = array(
			'account_id' => $this->_id,
			'username' => $mapping,
			'date_created' => $date->get(Zend_Date::W3C)
		);

		try {
			$result = $db->insert('accounts_maps', $data);

			return true;
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	public function update() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$where = $db->quoteInto('id = ?', $this->_id);
		$result = $db->update('accounts', $this->_data, $where);

		return new Account($this->id);
	}

	public function delete() {
		$session = Zend_Registry::get('nessquik');
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);
		$scanner = new Audit_Server($config->vscan->default);

		try {
			$audits = $this->audit->getAudits(0,0);
			$policies = $this->policy->getPolicies(0,0);

			foreach($audits as $info) {
				$audit = new Audit($info['audit_id']);
				$result = $audit->delete();
			}

			foreach($policies as $info) {
				$policy = new Policy($info['policy_id']);
				$result = $policy->delete();
			}

			$where = $db->quoteInto('id = ?', $this->_id);
			$result = $db->delete('accounts', $where);

			try {
				$result = $scanner->adapter->deleteUser($this->username);
			} catch (Exception $error) {
				$log->err($error->getMessage());
				$log->info('Scanner account may have already been removed');
			}

			if (isset($session->siteAudit['accountId'])) {
				if ($this->id == $session->siteAudit['accountId']) {
					$session->siteAudit = null;
					Zend_Registry::set('nessquik', $session);
				}
			}

			return true;
		} catch (Exception $error) {
			throw new Account_Exception($error->getMessage());
		}
	}

	public function deleteAccountMapping($mapId) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		try {
			$where[] = $db->quoteInto('account_id = ?', $this->_id);
			$where[] = $db->quoteInto('id = ?', $mapId);
			$result = $db->delete('accounts_maps', $where);
			return true;
		} catch (Exception $error) {
			throw new Account_Exception($error->getMessage());
		}

	}

	public function getMappings($page = 1, $limit = 15) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('accounts_maps', array('id', 'account_id', 'username'))
			->where('account_id = ?', $this->_id);

		if (!empty($limit)) {
			$sql->limitPage($page, $limit);
		}

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			return $stmt->fetchAll();
		} catch (Exception $error) {
			throw new Account_Exception($error->getMessage());
		}
	}

	/**
	* @throws Account_Exception
	*/
	public function setPrimaryRole($primaryRole) {
		$found = false;

		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		try {
			$data = array(
				'primary_role' => $primaryRole
			);
			$where = $db->quoteInto('id = ?', $this->_id);
			$db->update('accounts', $data, $where);
			$this->primary_role = $primaryRole;
			return true;
		} catch (Exception $error) {
			$log->err($error->getMessage());
			throw new Account_Exception($error->getMessage());
		}
	}

	public function getRoles() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);
		$tmp = array();

		$sql = $db->select()
			->from('accounts_roles', 'role_id')
			->where('accounts_roles.account_id = ?', $this->_id);

		try {
			$stmt = $sql->query();
			$result = $stmt->fetchAll();

			if (empty($result)) {
				return array();
			} else {
				foreach($result as $key => $val) {
					$tmp[] = $val['role_id'];
				}
				return $tmp;
			}
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return array();
		}
	}

	public function setPassword($password) {
		$log = App_Log::getInstance(self::IDENT);
		$authTypes = Authentication_Util::authTypes();
		$result = false;

		if (Authentication_Util::hasAuthType('DbTable')) {
			$result = $this->_setDatabasePassword($password);

			if ($result === false) {
				return $result;
			} else {
				$this->password = $password;
			}
		} else {
			$log->err('The database authentication type is not configured; skipping it');
		}

		return $result;
	}

	protected function _setDatabasePassword($password) {
		$log = App_Log::getInstance(self::IDENT);
		$auth = Ini_Authentication::getInstance();
		$hashedPassword = md5($password);

		foreach ($auth->auth as $key => $type) {
			$db = App_Db::getInstance($type->params->adapter);

			if ($type->adapter != 'DbTable') {
				continue;
			}

			try {
				$data = array(
					$type->params->credentialColumn => $hashedPassword
				);
				$where = $db->quoteInto(sprintf('%s = ?', $type->params->identityColumn), $this->username);
				$db->update($type->params->tableName, $data, $where);
			} catch (Exception $error) {
				$log->err($error->getMessage());
				return false;
			}
		}

		return true;
	}

	public function isFirstBoot() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('accounts')
			->where('id = ?', $this->_id)
			->where('firstboot = ?', '1');

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();

			if ($stmt->rowCount() > 0) {
				return true;
			} else {
				return false;
			}
		} catch (Exception $error) {
			throw new Account_Exception($error->getMessage());
		}
	}

	public function setFirstBoot($switch = 'off') {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);
		$switch = strtolower($switch);

		if ($switch == 'on') {
			$status = 1;
		} else {
			$status = 0;
		}

		$data = array(
			'firstboot' => $status
		);

		try {
			$where = $db->quoteInto('id = ?', $this->_id);
			$result = $db->update('accounts', $data, $where);
			return true;
		} catch (Exception $error) {
			throw new Account_Exception($error->getMessage());
		}
	}
}

?>
