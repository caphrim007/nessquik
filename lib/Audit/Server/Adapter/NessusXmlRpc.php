<?php

/**
* @author Tim Rupp <caphrim007@gmail.com>
*/
class Audit_Server_Adapter_NessusXmlRpc {
	protected $_client;
	protected $_data;
	protected $_uri;

	/**
	* @var Zend_Http_Client Default HTTP client to use for access
	*/
	protected static $_defaultClient;

	const IDENT = __CLASS__;

	const CLIENT_ERR_UNAUTHORIZED = 401;

	public function __construct($config = null) {
		$params = array();

		if ($config instanceof Zend_Config) {
			$config = $config->toArray();
		} else if (!is_array($config)) {
			throw new Exception('Configuration parameters must be an array or instance of Zend_Config');
		}

		/*
		* normalize the config and merge it with the defaults
		*/
		if (array_key_exists('params', $config)) {
			// can't use array_merge() because keys might be integers
			foreach ((array) $config['params'] as $key => $value) {
				$params[$key] = $value;
			}
		}

		$this->_data = $params;

		$uri = Zend_Uri::factory('https');

		$uri->setHost($this->host);
		$uri->setPort($this->port);
		$this->_uri = $uri;

		$client = new Zend_Http_Client;
		$client->setCookieJar();
		$this->_client = $client;
		$this->login($this->username, $this->password);
	}

	public function __get($key) {
		if (isset($this->_data[$key])) {
			return $this->_data[$key];
		} else {
			return false;
		}
	}

	public function addUser($login, $password, $admin = 0) {
		$client = $this->_client;

		$client->resetParameters();
		$client->setUri(sprintf('%s/users/add', $this->_uri->getUri()));
		$client->setParameterPost('login', $login);
		$client->setParameterPost('password', $password);

		if ($admin == 0 || $admin == 1) {
			$client->setParameterPost('admin', $admin);
		}

		$resp = $client->request('POST');
		$lastMesg = $client->getLastResponse()->getMessage();
		if ($lastMesg == 'Unauthorized') {
			throw new Exception_Unauthorized('Account is unauthorized', self::CLIENT_ERR_UNAUTHORIZED);
		}

		$xml = new Zend_Config_Xml($resp->getBody());
		if ($resp->isSuccessful() && $xml->status != 'ERROR') {
			return true;
		} else {
			throw new Exception($xml->contents);
		}
	}

	public function deleteUser($login) {
		$client = $this->_client;

		$client->resetParameters();
		$client->setUri(sprintf('%s/users/delete', $this->_uri->getUri()));
		$client->setParameterPost('login', $login);

		$resp = $client->request('POST');
		$lastMesg = $client->getLastResponse()->getMessage();
		if ($lastMesg == 'Unauthorized') {
			throw new Exception_Unauthorized('Account is unauthorized', self::CLIENT_ERR_UNAUTHORIZED);
		}

		$xml = new Zend_Config_Xml($resp->getBody());
		if ($resp->isSuccessful() && $xml->status != 'ERROR') {
			return true;
		} else {
			throw new Exception($xml->contents);
		}
	}

	public function hasUser($login) {
		$users = $this->listUsers();

		foreach($users as $user) {
			if ($user['name'] == $login) {
				return true;
			}
		}

		return false;
	}

	public function changePassword($login, $password) {
		$client = $this->_client;

		$client->resetParameters();
		$client->setUri(sprintf('%s/users/chpasswd', $this->_uri->getUri()));
		$client->setParameterPost('login', $login);
		$client->setParameterPost('password', $password);

		$resp = $client->request('POST');
		$lastMesg = $client->getLastResponse()->getMessage();
		if ($lastMesg == 'Unauthorized') {
			throw new Exception_Unauthorized('Account is unauthorized', self::CLIENT_ERR_UNAUTHORIZED);
		}

		$xml = new Zend_Config_Xml($resp->getBody());
		if ($resp->isSuccessful() && $xml->status != 'ERROR') {
			return true;
		} else {
			throw new Exception($xml->contents);
		}
	}

	public function listUsers() {
		$client = $this->_client;

		$client->resetParameters();
		$client->setUri(sprintf('%s/users/list', $this->_uri->getUri()));

		$resp = $client->request('GET');
		$lastMesg = $client->getLastResponse()->getMessage();
		if ($lastMesg == 'Unauthorized') {
			throw new Exception_Unauthorized('Account is unauthorized', self::CLIENT_ERR_UNAUTHORIZED);
		}

		$xml = new Zend_Config_Xml($resp->getBody());
		if ($resp->isSuccessful() && $xml->status != 'ERROR') {
			return $xml->contents->users->user->toArray();
		} else {
			throw new Exception($xml->contents);
		}
	}

	public function listPlugins() {
		$client = $this->_client;

		$client->setUri(sprintf('%s/plugins/list', $this->_uri->getUri()));

		$resp = $client->request('GET');
		$lastMesg = $client->getLastResponse()->getMessage();
		if ($lastMesg == 'Unauthorized') {
			throw new Exception_Unauthorized('Account is unauthorized', self::CLIENT_ERR_UNAUTHORIZED);
		}

		$xml = new Zend_Config_Xml($resp->getBody());
		if ($resp->isSuccessful() && $xml->status != 'ERROR') {
			return $xml->contents->pluginFamilyList->family->toArray();
		} else {
			throw new Exception($xml->contents);
		}
	}

	public function listPluginsInFamily($family) {
		$client = $this->_client;

		$client->resetParameters();
		$client->setUri(sprintf('%s/plugins/list/family', $this->_uri->getUri()));
		$client->setParameterPost('family', $family);

		$resp = $client->request('POST');
		$lastMesg = $client->getLastResponse()->getMessage();
		if ($lastMesg == 'Unauthorized') {
			throw new Exception_Unauthorized('Account is unauthorized', self::CLIENT_ERR_UNAUTHORIZED);
		}

		$xml = new Zend_Config_Xml($resp->getBody());
		if ($resp->isSuccessful() && $xml->status != 'ERROR') {
			$result = $xml->contents->pluginList->plugin->toArray();

			/**
			* This is a kludge/workaround because the return plugin
			* list is not always an array of arrays. Sometimes it
			* can be an array that has a single plugin's information
			* in it (for instance when a particular family only has
			* one plugin in it).
			*/
			if (isset($result['pluginID'])) {
				return array($result);
			} else {
				return $result;
			}
		} else {
			throw new Exception($xml->contents);
		}
	}

	public function lookupPlugin($plugin) {
		$client = $this->_client;

		$client->resetParameters();
		$client->setUri(sprintf('%s/plugins/description', $this->_uri->getUri()));
		$client->setParameterPost('fname', $plugin);

		$resp = $client->request('POST');
		$lastMesg = $client->getLastResponse()->getMessage();
		if ($lastMesg == 'Unauthorized') {
			throw new Exception_Unauthorized('Account is unauthorized', self::CLIENT_ERR_UNAUTHORIZED);
		}

		$xml = new Zend_Config_Xml(utf8_encode($resp->getBody()));
		if ($resp->isSuccessful() && $xml->status != 'ERROR') {
			return $xml->contents->pluginDescription->toArray();
		} else {
			throw new Exception($xml->contents);
		}
	}

	public function pluginPreferences() {
		$client = $this->_client;

		$client->resetParameters();
		$client->setUri(sprintf('%s/plugins/preferences', $this->_uri->getUri()));

		$resp = $client->request('GET');
		$lastMesg = $client->getLastResponse()->getMessage();
		if ($lastMesg == 'Unauthorized') {
			throw new Exception_Unauthorized('Account is unauthorized', self::CLIENT_ERR_UNAUTHORIZED);
		}

		$xml = new Zend_Config_Xml($resp->getBody());
		if ($resp->isSuccessful() && $xml->status != 'ERROR') {
			return $xml->contents->PluginsPreferences->item->toArray();
		} else {
			throw new Exception($xml->contents);
		}
	}

	public function serverPreferences() {
		$client = $this->_client;

		$client->resetParameters();
		$client->setUri(sprintf('%s/preferences/list', $this->_uri->getUri()));

		$resp = $client->request('GET');
		$lastMesg = $client->getLastResponse()->getMessage();
		if ($lastMesg == 'Unauthorized') {
			throw new Exception_Unauthorized('Account is unauthorized', self::CLIENT_ERR_UNAUTHORIZED);
		}

		$xml = new Zend_Config_Xml($resp->getBody());
		if ($resp->isSuccessful() && $xml->status != 'ERROR') {
			return $xml->contents->ServerPreferences->preference->toArray();
		} else {
			throw new Exception($xml->contents);
		}
	}

	public function listPolicies() {
		$client = $this->_client;

		$client->resetParameters();
		$client->setUri(sprintf('%s/policy/list', $this->_uri->getUri()));

		$resp = $client->request('GET');
		$lastMesg = $client->getLastResponse()->getMessage();
		if ($lastMesg == 'Unauthorized') {
			throw new Exception_Unauthorized('Account is unauthorized', self::CLIENT_ERR_UNAUTHORIZED);
		}

		$xml = new Zend_Config_Xml($resp->getBody());
		if ($resp->isSuccessful() && $xml->status != 'ERROR') {
			if (is_string($xml->contents->policies)) {
				$policies = trim($xml->contents->policies);
			
				if (empty($policies)) {
					return array();
				} else {
					return $xml->contents->policies->policy->toArray();
				}
			} else {
				return $xml->contents->policies->policy->toArray();
			}
		} else {
			throw new Exception($xml->contents);
		}
	}

	public function savePolicy($policyId = 0, $options = array(), $preferences = array()) {
		$log = App_Log::getInstance(self::IDENT);
		$client = $this->_client;

		$client->resetParameters();
		$client->setUri(sprintf('%s/policy/add', $this->_uri->getUri()));
		$client->setParameterPost('policy_id', $policyId);

		if (isset($options['policy_name'])) {
			$client->setParameterPost('policy_name', $options['policy_name']);
		}

		if (isset($options['policy_shared'])) {
			$client->setParameterPost('policy_shared', $options['policy_shared']);
		}

		foreach($preferences as $key => $val) {
			$log->debug(sprintf('Preference: "%s". Value: "%s"', $key, $val));
			$client->setParameterPost($key, $val);
		}

		$resp = $client->request('POST');
		$lastMesg = $client->getLastResponse()->getMessage();
		if ($lastMesg == 'Unauthorized') {
			throw new Exception_Unauthorized('Account is unauthorized', self::CLIENT_ERR_UNAUTHORIZED);
		}

		$xml = new Zend_Config_Xml($resp->getBody());
		if ($resp->isSuccessful() && $xml->status != 'ERROR') {
			/**
			* Might I say that Nessus is mighty flaming gay.
			* It appears that if you are saving an existing policy
			* that Nessus will not return the policy that you are saving
			* like it will if you're saving a new policy.
			*/
			if (empty($policyId)) {
				return $xml->contents->policy->toArray();
			} else {
				if (is_string($xml->contents)) {
					$policy = trim($xml->contents);
					if (empty($policy)) {
						return array();
					} else {
						return $xml->contents->policy->toArray();
					}
				} else {
					return $xml->contents->policy->toArray();
				}
			}
		} else {
			throw new Exception($xml->contents);
		}
	}

	public function deletePolicy($policyId) {
		$client = $this->_client;

		$client->resetParameters();
		$client->setUri(sprintf('%s/policy/delete', $this->_uri->getUri()));
		$client->setParameterPost('policy_id', $policyId);

		$resp = $client->request('POST');
		$lastMesg = $client->getLastResponse()->getMessage();
		if ($lastMesg == 'Unauthorized') {
			throw new Exception_Unauthorized('Account is unauthorized', self::CLIENT_ERR_UNAUTHORIZED);
		}

		$xml = new Zend_Config_Xml($resp->getBody());
		if ($resp->isSuccessful() && $xml->status != 'ERROR') {
			return true;
		} else {
			throw new Exception($xml->contents);
		}
	}

	public function renamePolicy($policyId, $policyName) {
		$client = $this->_client;

		$client->resetParameters();
		$client->setUri(sprintf('%s/policy/rename', $this->_uri->getUri()));
		$client->setParameterPost('policy_id', $policyId);
		$client->setParameterPost('policy_name', $policyName);

		$resp = $client->request('POST');
		$lastMesg = $client->getLastResponse()->getMessage();
		if ($lastMesg == 'Unauthorized') {
			throw new Exception_Unauthorized('Account is unauthorized', self::CLIENT_ERR_UNAUTHORIZED);
		}

		$xml = new Zend_Config_Xml($resp->getBody());
		if ($resp->isSuccessful() && $xml->status != 'ERROR') {
			return true;
		} else {
			throw new Exception($xml->contents);
		}
	}

	public function startScan($policyId, $scanName, $targets) {
		$client = $this->_client;

		if (is_array($targets)) {
			$targets = implode(',', $targets);
		}

		if (empty($targets)) {
			throw new Exception('You must specify at least one target to scan');
		}

		$client->resetParameters();
		$client->setUri(sprintf('%s/scan/new', $this->_uri->getUri()));
		$client->setParameterPost('policy_id', $policyId);
		$client->setParameterPost('scan_name', $scanName);
		$client->setParameterPost('target', $targets);

		$resp = $client->request('POST');
		$lastMesg = $client->getLastResponse()->getMessage();
		if ($lastMesg == 'Unauthorized') {
			throw new Exception_Unauthorized('Account is unauthorized', self::CLIENT_ERR_UNAUTHORIZED);
		}

		$xml = new Zend_Config_Xml($resp->getBody());
		if ($resp->isSuccessful() && $xml->status != 'ERROR') {
			return $xml->contents->scan->toArray();
		} else {
			throw new Exception($xml->contents);
		}
	}

	public function stopScan($scanId) {
		$client = $this->_client;

		$client->resetParameters();
		$client->setUri(sprintf('%s/scan/stop', $this->_uri->getUri()));
		$client->setParameterPost('scan_uuid', $scanId);

		$resp = $client->request('POST');
		$lastMesg = $client->getLastResponse()->getMessage();
		if ($lastMesg == 'Unauthorized') {
			throw new Exception_Unauthorized('Account is unauthorized', self::CLIENT_ERR_UNAUTHORIZED);
		}

		$xml = new Zend_Config_Xml($resp->getBody());
		if ($resp->isSuccessful() && $xml->status != 'ERROR') {
			if (isset($xml->contents->scan)) {
				return $xml->contents->scan->toArray();
			} else {
				throw new Exception('XML-RPC call succeeded but content was empty. Perhaps audit has already been stopped?');
			}
		} else {
			throw new Exception($xml->contents);
		}
	}

	public function listScans() {
		$client = $this->_client;

		$client->resetParameters();
		$client->setUri(sprintf('%s/scan/list', $this->_uri->getUri()));

		$resp = $client->request('GET');
		$lastMesg = $client->getLastResponse()->getMessage();
		if ($lastMesg == 'Unauthorized') {
			throw new Exception_Unauthorized('Account is unauthorized', self::CLIENT_ERR_UNAUTHORIZED);
		}

		$xml = new Zend_Config_Xml($resp->getBody());
		if ($resp->isSuccessful() && $xml->status != 'ERROR') {
			if (is_string($xml->contents->scans->scanList)) {
				$scans = trim($xml->contents->scans->scanList);
				if (empty($scans)) {
					return array();
				} else {
					return $xml->contents->scans->scanList->scan->toArray();
				}
			} else {
				$contents = $xml->contents->scans->scanList->scan->toArray();
				if (isset($contents['uuid'])) {
					// Nessus doesnt return a fucking array of arrays
					return array($contents);
				} else {
					return $contents;
				}
			}
		} else {
			throw new Exception($xml->contents);
		}
	}

	public function copyPolicy($policyId) {
		$client = $this->_client;

		$client->resetParameters();
		$client->setUri(sprintf('%s/policy/copy', $this->_uri->getUri()));
		$client->setParameterPost('policy_id', $policyId);

		$resp = $client->request('POST');
		$lastMesg = $client->getLastResponse()->getMessage();
		if ($lastMesg == 'Unauthorized') {
			throw new Exception_Unauthorized('Account is unauthorized', self::CLIENT_ERR_UNAUTHORIZED);
		}

		$xml = new Zend_Config_Xml($resp->getBody());
		if ($resp->isSuccessful() && $xml->status != 'ERROR') {
			return $xml->contents->policy->toArray();
		} else {
			throw new Exception($xml->contents);
		}
	}

	public function listReports() {
		$client = $this->_client;

		$client->resetParameters();
		$client->setUri(sprintf('%s/report/list', $this->_uri->getUri()));

		$resp = $client->request('GET');
		$lastMesg = $client->getLastResponse()->getMessage();
		if ($lastMesg == 'Unauthorized') {
			throw new Exception_Unauthorized('Account is unauthorized', self::CLIENT_ERR_UNAUTHORIZED);
		}

		$xml = new Zend_Config_Xml($resp->getBody());
		if ($resp->isSuccessful() && $xml->status != 'ERROR') {
			if (is_string($xml->contents->reports)) {
				$reports = trim($xml->contents->reports);
				if (empty($reports)) {
					return array();
				}
			} else if (is_string($xml->contents->reports->report)) {
				$reports = trim($xml->contents->reports->report);
				if (empty($reports)) {
					return array();
				} else {
					return $xml->contents->reports->report->toArray();
				}
			} else {
				$contents = $xml->contents->reports->report->toArray();
				if (isset($contents['name'])) {
					// Nessus doesnt return a fucking array of arrays
					return array($contents);
				} else {
					return $contents;
				}
			}
		} else {
			throw new Exception($xml->contents);
		}
	}

	public function downloadReport($reportId, $inline = false, $version = 1, $raw = false) {
		$client = $this->_client;

		$client->resetParameters();
		$client->setUri(sprintf('%s/file/report/download', $this->_uri->getUri()));
		$client->setParameterGet('report', $reportId);

		if ($version == 1) {
			$client->setParameterGet('v1', 'true');
		}

		if (is_bool($inline)) {
			$client->setParameterGet('inline', $inline);
		}

		$resp = $client->request('GET');
		$lastMesg = $client->getLastResponse()->getMessage();
		if ($lastMesg == 'Unauthorized') {
			throw new Exception_Unauthorized('Account is unauthorized', self::CLIENT_ERR_UNAUTHORIZED);
		}

		if ($resp->isSuccessful()) {
			if ($raw === true) {
				return $resp->getBody();
			} else {
				$xml = new Zend_Config_Xml($resp->getBody());
				if ($xml->status != 'ERROR') {
					return $xml->Policy->toArray();
				} else {
					throw new Exception($xml->contents);
				}
			}
		} else {

		}
	}

	public function deleteReport($reportId) {
		$client = $this->_client;

		$client->resetParameters();
		$client->setUri(sprintf('%s/report/delete', $this->_uri->getUri()));
		$client->setParameterPost('report', $reportId);

		$resp = $client->request('POST');
		$lastMesg = $client->getLastResponse()->getMessage();
		if ($lastMesg == 'Unauthorized') {
			throw new Exception_Unauthorized('Account is unauthorized', self::CLIENT_ERR_UNAUTHORIZED);
		}

		$xml = new Zend_Config_Xml($resp->getBody());
		if ($resp->isSuccessful() && $xml->status != 'ERROR') {
			return true;
		} else {
			throw new Exception($xml->contents);
		}
	}

	public function exportHtml($reportId) {
		$xmlString = $this->downloadReport($reportId, true, true);
		$format = $this->getStylesheet();
		$format = str_replace('stylesheet version="2.0"', 'stylesheet version="1.0"', $format);

		$xml = new DOMDocument();
		$xml->loadXML($xmlString);

		# Start XSLT
		$xslt = new XSLTProcessor();
		$xsl = new DOMDocument();

		$xsl->loadXML($format, LIBXML_NOCDATA);
		$xslt->importStylesheet($xsl);

		echo $xslt->transformToXML($xml);
	}

	public function login($username, $password) {
		$log = App_Log::getInstance(self::IDENT);
		$client = $this->_client;

		$client->resetParameters();
		$client->setUri(sprintf('%s/login', $this->_uri->getUri()));
		$client->setParameterPost('login', $username);
		$client->setParameterPost('password', $password);

		$resp = $client->request('POST');
		$xml = new Zend_Config_Xml($resp->getBody());
		if ($resp->isSuccessful() && $xml->status != 'ERROR') {
			return true;
		} else if ($xml->status == 'ERROR') {
			$log->err($client->getLastResponse());
			$content = $xml->contents;

			switch($content) {
				case 'Invalid login':
					throw new Exception_InvalidLogin('Invalid login');
				default:
					return false;
			}
		} else {
			$log->err($client->getLastResponse());
			return false;
		}
	}

	public function countPlugins() {
		$total = 0;
		$plugins = $this->listPlugins();

		foreach($plugins as $family) {
			$total += $family['numFamilyMembers'];
		}

		return $total;
	}
}

?>
