<?php

set_time_limit(0);

if (!defined('_ABSPATH')) {
	define('_ABSPATH', dirname(dirname(__FILE__)));
}

if (!defined('IDENT')) {
	define('IDENT', 'ImportNessquik25Audits');
}

require _ABSPATH.'/lib/Autoload.php';

$log	= App_Log::getInstance(IDENT);
$sysconf = Ini_Config::getInstance();
$config	= Ini_Maintenance::getInstance();
$cg 	= new Zend_Console_Getopt(
	array(
		'help'=> 'Display this help and exit',
		'run|r'	=> 'Run maintenance',
		'host|h=s' => 'The host of the nessquik 2.5 database to connect to',
		'port|p=s' => 'The port of the nessquik 2.5 database to connect to',
		'username|U=s' => 'The username to connect to the nessquik 2.5 database with',
		'password|W=s' => 'The password of the username to connect to the nessquik 2.5 database with',
		'dbname|d=s' => 'The name of the nessquik 2.5 database to connect to'
	)
);
$run = false;
$host = 'localhost';
$port = 3306;
$username = 'root';
$password = '';
$dbname = 'nessquik';

try {
	$opts = $cg->parse();
} catch (Zend_Console_Getopt_Exception $e) {
	usage($e);
	exit;
}

if (isset($opts->help)) {
	usage($opts);
	exit;
}

if (isset($opts->r)) {
	$run = true;
}

if (isset($opts->h)) {
	$host = $opts->h;
}

if (isset($opts->p)) {
	$port = $opts->p;
}

if (isset($opts->U)) {
	$username = $opts->U;
}

if (isset($opts->W)) {
	$password = $opts->W;
}

if (isset($opts->d)) {
	$dbname = $opts->d;
}

if ($run === false) {
	usage($opts);
	exit;
}

if ($sysconf->misc->firstboot == 1) {
	$log->info('System has not been set-up yet. Firstboot flag still set in config file');
	exit;
}

try {
	$auditsCreated = 0;
	$permissions = new Permissions;
	$options = array(
		'adapter' => "Pdo_Mysql",
		'params' => array(
			'username' => $username,
			'password' => $password,
			'host' => $host,
			'port' => $port,
			'dbname' => $dbname,
		)
	);

	$oldConfig = new Zend_Config($options);
	$oldDb = App_Db::factory($oldConfig);
	Zend_Registry::set('oldDb', $oldDb);

	$profiles = _getProfilesList();

	if (empty($profiles)) {
		throw new Exception('No audit profiles were found in the nessquik 2.5 database you specified');
	}

	foreach($profiles as $profile) {
		$log->debug(sprintf('Asked to import profile with nessquik 2.5 ID "%s"', $profile['profile_id']));

		$accountId = Account_Util::getId($profile['username']);
		$account = new Account($accountId);
		Zend_Registry::set('account', $account);

		$tmp = $account->scanner->getScanners();
		if (empty($tmp)) {
			$log->info(sprintf('User account "%s" does not have permission to use any scanners', $account->username));
			continue;
		} else {
			// I'm not concerned with worrying which scanner to put
			// the imported scan on. I'll just use the first returned
			$scanner = $tmp[0]['id'];
		}

		$log->debug('Getting profile settings');
		$settings = _getProfileSettings($profile['profile_id']);

		$log->debug('Getting machine list');
		$targets = _getMachineList($profile['profile_id']);

		$log->debug('Getting plugin list');
		$plugins = _getPluginList($profile['profile_id']);

		$log->debug('Assigning port scanners to list of individual plugins');
		// These are port scanners
		$plugins['individual_plugin']['14274'] = 'on';
		$plugins['individual_plugin']['10335'] = 'on';
		$plugins['individual_plugin']['14272'] = 'on';
		$plugins['individual_plugin']['34220'] = 'on';
		$plugins['individual_plugin']['10180'] = 'on';

		$log->debug(sprintf('Creating new policy from existing 2.5 scan; id "%s", name "%s"', $settings['setting_id'], $settings['setting_name']));
		$policyPref = array(
			'preference' => array(
				'policy_name' => $settings['setting_name'],
				'port_range' => $settings['port_range'],
				'ping_host_first' => $settings['ping_host_first']
			),
			'plugin_selection' => $plugins
		);
		$log->debug('This may take a while if you have a lot of severities in your 2.5 profile. Severities are converted to individual plugins');
		$policy = _createPolicy($policyPref);

		$auditPref = array(
			'policyId' => $policy->id,
			'included' => $targets,
			'name' => $settings['setting_name'],
			'reportFormat' => $settings['report_format'],
			'subject' => $settings['custom_email_subject'],
			'sendToMe' => 'yes',
			'scanner' => $scanner
		);

		if (empty($settings['alternative_email_list'])) {
			$auditPref['sendToOthers'] = 'no';
		} else {
			$auditPref['sendToOthers'] = 'yes';
			$auditPref['recipients'] = explode(',', $settings['alternative_email_list']);
		}

		if ($settings['recurring'] == 1) {
			$log->debug('Merging in new recurrence settings');
			$auditPref['scheduling'] = true;
			$auditPref = _mergeRecurrenceSettings($auditPref, $profile);
		} else {
			$log->debug('No scheduling was enabled');
			$auditPref['scheduling'] = false;
			$auditPref['enableScheduling'] = 'doesNotRepeat';
		}
		$log->debug('Creating new audit');

		try {
			$audit = _createAudit($auditPref);
			$auditsCreated = $auditsCreated + 1;
		} catch (Exception_NoTargets $error) {
			$log->info($error->getMessage());
		}
	}
} catch (Exception $error) {
	echo $error->getMessage()."\n";
	$log->err($error->getMessage());
	$log->debug(sprintf('Audit prefs: "%s"', json_encode($auditPref)));
	$log->debug(sprintf('Policy prefs: "%s"', json_encode($policyPref)));
}

echo sprintf("Imported %s audits into nessquik\n", $auditsCreated);

function usage($error) {
	echo sprintf("\n%s\n", $error->getUsageMessage());
}

function _mergeRecurrenceSettings($auditPref, $profile) {
	$log = App_Log::getInstance(IDENT);
	$recurrence = _getRecurrenceSettings($profile['profile_id']);
	$today = new Zend_Date($recurrence['specific_time'], 'YYYY-MM-dd HH:mm:ss');

	switch($recurrence['recur_type']) {
		case 'D':
			$auditPref['repeatEvery'] = $recurrence['the_interval'];
			$auditPref['enableScheduling'] = 'daily';
			break;
		case 'W':
			$auditPref['repeatEvery'] = $recurrence['the_interval'];
			$tmp = explode(';', $recurrence['rules_string']);

			$auditPref['enableScheduling'] = 'weekly';
			$auditPref['repeatOn'] = array();
			if (in_array('sun:1', $tmp)) {
				$auditPref['repeatOn'][] = 0;
			}
			if (in_array('mon:1', $tmp)) {
				$auditPref['repeatOn'][] = 1;
			}
			if (in_array('tue:1', $tmp)) {
				$auditPref['repeatOn'][] = 2;
			}
			if (in_array('wed:1', $tmp)) {
				$auditPref['repeatOn'][] = 3;
			}
			if (in_array('thu:1', $tmp)) {
				$auditPref['repeatOn'][] = 4;
			}
			if (in_array('fri:1', $tmp)) {
				$auditPref['repeatOn'][] = 5;
			}
			if (in_array('sat:1', $tmp)) {
				$auditPref['repeatOn'][] = 6;
			}
			break;
		case 'M':
			$auditPref['repeatEvery'] = $recurrence['the_interval'];
			$auditPref['enableScheduling'] = 'monthly';
			$tmp = explode(':', $recurrence['rules_string']);

			switch($tmp[0]) {
				case 'gen':
					$auditPref['repeatBy'] = 'byWeekDay';
					break;
				case 'day':
					$auditPref['repeatBy'] = 'byMonthDay';
					break;
				default:
					$log->info(sprintf('Unknown month repeatBy "%s" found in rules_string', $tmp[0]));
					break;
			}
			break;
		default:
			$log->info(sprintf('Unknown recurrence type "%s" found in recurrence settings', $recurrence['recur_type']));
			break;
	}

	$auditPref['rangeStart'] = $today->toString('MM/dd/YYYY');
	$auditPref['rangeEnd'] = 'never';

	$auditPref['startOnTime'] = $today->toString('hh:mma');

	return $auditPref;
}

/**
* Returns a list of scan profiles from a nessquik 2.5 database
*
* Returned list is an array of arrays with each element in
* the main array having the following indexs
*
*	profile_id
*	username
*	date_scheduled
*	date_finished
*	status
*	cancel
*
* ex.
*
*	profile_id	4c93695ec15c06e5412e1268f5f17093
*	username	andylego
*	date_scheduled	"2008-01-17 13:06:19"
*	date_finished	"2008-01-17 13:22:43"
*	status		F
*	cancel		N
*/
function _getProfilesList() {
	$config = Ini_Config::getInstance();
	$log = App_Log::getInstance(IDENT);
	$db = Zend_Registry::get('oldDb');

	$sql = $db->select()
		->from('profile_list');

	try {
		$log->debug($sql->__toString());

		$stmt = $sql->query();
		return $stmt->fetchAll();
	} catch (Exception $error) {
		$log->err($error->getMessage());
		throw $error;
	}
}

/**
* Returns a list of settings for a scan stored in a nessquik
* 2.5 database
*
* Returned list is an array of arrays with each element in
* the main array having the following indexs
*
*	setting_id
*	username
*	profile_id
*	setting_name
*	setting_type
*	short_plugin_listing
*	ping_host_first
*	report_format
*	save_scan_report
*	port_range
*	custom_email_subject
*	alternative_email_list
*	alternative_cgibin_list
*	recurring
*	scanner_id
*
* ex.
*
*	setting_id		4
*	username		andylego
*	profile_id		4c93695ec15c06e5412e1268f5f17093
*	setting_name		"Scan of 131.225.18.99, 131.225.52.40..."
*	setting_type		"user"
*	short_plugin_listing	1
*	ping_host_first		0
*	report_format		"txt"
*	save_scan_report	0
*	port_range		"default"
*	custom_email_subject	"Nessus Scan Results"
*	alternative_email_list	""
*	alternative_cgibin_list	"/cgi-bin:/scripts"
*	recurring		0
*	scanner_id		1
*/
function _getProfileSettings($profileId) {
	$log = App_Log::getInstance(IDENT);
	$db = Zend_Registry::get('oldDb');

	$sql = $db->select()
		->from('profile_settings')
		->where('profile_id = ?', $profileId);

	try {
		$log->debug($sql->__toString());
		$stmt = $sql->query();
		$result = $stmt->fetchAll();

		if (empty($result)) {
			return array();
		} else {
			return $result[0];
		}
	} catch (Exception $error) {
		$log->err($error->getMessage());
		throw $error;
	}
}

/**
* Returns a list of targets for a scan stored in a nessquik
* 2.5 database
*
* Returned list is an array of arrays with each element in
* the main array having the following indexs
*
*	network
*	hostname
*	cluster
*
* ex.
*
*	network => Array(
*		131.225.10.9,
*		131.225.95.60
*	)
*	hostname => Array(
*		S-S-WH11NW-CMS,
*		WWW04-VHOSTS.FNAL.GOV
*	)
*	cluster => Array(
*		273468
*	)
*
* While the machine list in nessquik 2.5 includes a "whi" type,
* those entries will be merged into the network and hostname
* array indices.
*
* |   1022 | 59b3ee1e11b658f78803c0645d3c5aee | :reg:131.225.10.9
* |   8632 | 1296ce20755eb792010bae2c5c154f2b | :reg:S-S-WH11NW-CMS
* |   3854 | 7e938eba562100783f55fa6c66d33c08 | :whi:131.225.95.60
* |   3981 | efe685141bdcb225dd1a98cfcd15ec90 | :whi:WWW04-VHOSTS.FNAL.GOV
* |   7993 | e696a04f6bfaa607d451dd4c8b2bba74 | :clu:273468
* |   7169 | 8663a65ab46efdc0e3f23c8930c8cc3c | :vho:www-theory.fnal.gov[131.225.70.73]
*/
function _getMachineList($profileId) {
	$return = array();

	$log = App_Log::getInstance(IDENT);
	$db = Zend_Registry::get('oldDb');

	$sql = $db->select()
		->from('profile_machine_list')
		->where('profile_id = ?', $profileId);

	try {
		$log->debug($sql->__toString());
		$stmt = $sql->query();
		$results = $stmt->fetchAll();

		if (empty($results)) {
			return array();
		}

		foreach ($results as $result) {
			$tmp = explode(':', $result['machine']);

			if (!isset($tmp[2])) {
				continue;
			}

			switch($tmp[1]) {
				case 'whi':
				case 'reg':
					if (Ip::isIpAddress($tmp[2])) {
						$return['network'][] = $tmp[2];
					} else {
						$return['hostname'][] = $tmp[2];
					}
					break;
				case 'vho':
					preg_match('/^(?<hostname>[a-zA-Z0-9 ._-]+)\[(?<ip>' . RE_IP_ADD . '|' . RE_IPV6_ADD . ')\]$/', $tmp[2], $matches);
					$hostname = trim($matches['hostname']);
					$return['hostname'][] = $hostname;
					break;
				case 'clu':
					$return['cluster'][] = $tmp[2];
					break;
			}
		}

		return $return;
	} catch (Exception $error) {
		$log->err($error->getMessage());
		throw $error;
	}
}

/**
* Returns a list of plugins to add to a nessquik 2.6 policy
*
* As of Nessus 4.2, the only types of plugin things that now
* exist are Families and individual plugins. This method
* will enumerate Families as best as it can and add those to
* new audit policies.
*
* For other types of plugin things (severities, special plugins)
* it will enumerate the individual plugin IDs (for severities)
* and Families and Individual Plugins (for special plugins)
*/
function _getPluginList($profileId) {
	$plugins = array();

	$log = App_Log::getInstance(IDENT);
	$db = Zend_Registry::get('oldDb');

	$sql = $db->select()
		->from('profile_plugin_list')
		->where('profile_id = ?', $profileId);

	try {
		$log->debug($sql->__toString());
		$stmt = $sql->query();
		$results = $stmt->fetchAll();

		if (empty($results)) {
			// Policies must have at least 1 plugin.
			// Default to "all" if no plugins were originally set
			return array('all' => array(0 => 'yes'));
		}

		/**
		* Plugins are stored in table like so
		*
		* | row_id | profile_id                       | plugin_type | plugin                        |
		* +--------+----------------------------------+-------------+-------------------------------+
		* |      1 | 4c93695ec15c06e5412e1268f5f17093 | all         | all                           |
		* |    294 | 9675db8028bb44a1eb89413b620bee03 | all         | all                           |
		* |      4 | e94b99ce915693772bb6487b19ddcb56 | all         | all                           |
		* |      5 | 80f181baad886dd8e9ecc2dc31302706 | fam         | Windows : User management     |
		* |      6 | 80f181baad886dd8e9ecc2dc31302706 | fam         | Windows : Microsoft Bulletins |
		* |      7 | 80f181baad886dd8e9ecc2dc31302706 | fam         | Windows			    |
		*/
		foreach($results as $plugin) {
			switch($plugin['plugin_type']) {
				case 'all':
					// Specifying "all" overrides everything else
					return array('all' => array(0 => 'yes'));
				case 'fam':
					$plugins['family'][] = trim($plugin['plugin']);
					break;
				case 'sev':
					$enumerated = _enumerateSeverity($plugin['plugin']);

					if (empty($enumerated)) {
						$plugins['individual_plugin'] = $enumerated;
					} else {
						foreach($enumerated as $id => $status) {
							$plugins['individual_plugin'][$id] = 'on';
						}
					}

					unset($enumerated);
					break;
				case 'plu':
					$id = $plugin['plugin'];
					$plugins['individual_plugin'][$id] = 'on';
					break;
				case 'spe':
					$enumerated = _enumerateSpecialPlugin($plugin['plugin']);
					break;
			}
		}

		return $plugins;
	} catch (Exception $error) {
		$log->err($error->getMessage());
		throw $error;
	}
}

function _getRecurrenceSettings($profileId) {
	$log = App_Log::getInstance(IDENT);
	$db = Zend_Registry::get('oldDb');

	$sql = $db->select()
		->from('recurrence')
		->where('profile_id = ?', $profileId);

	try {
		$log->debug($sql->__toString());
		$stmt = $sql->query();
		$results = $stmt->fetchAll();

		if (empty($results)) {
			return array();
		} else {
			return $results[0];
		}
	} catch (Exception $error) {
		$log->err($error->getMessage());
	}
}

/**
* Returns a list of individual plugin IDs that are associated
* with a given nessquik 2.5 severity
*
* Since Nessus 4.2, severities are gone. This function enumerates
* the individual plugin IDs that are associated with a nessquik 2.5
* severity and returns them so that they can be assigned as individual
* plugins in the new nessquik audit policies
*
* @return array|struct
*/
function _enumerateSeverity($severity) {
	$plugins = array();

	$log = App_Log::getInstance(IDENT);
	$db = Zend_Registry::get('oldDb');

	$sql = $db->select()
		->from('plugins')
		->where('sev = ?', $severity);

	try {
		$log->debug($sql->__toString());
		$stmt = $sql->query();
		$results = $stmt->fetchAll();

		if (empty($results)) {
			return array();
		}

		foreach($results as $result) {
			$id = trim($result['pluginid']);
			$plugins[$id] = 'on';
		}

		return $plugins;
	} catch (Exception $error) {
		$log->err($error->getMessage());
		throw $error;
	}
}

/**
* Enumerates special plugins into individual and plugin families
*
* Since Nessus 4.2 only supports individual plugins and plugin
* families, and because I don't want to dick with this feature
* anymore. Speical plugins will be enumerated into their respective
* Individual and Family lists. Severities will be enumerated
* into their individual plugin lists.
*
* @return array|struct
*/
function _enumerateSpecialPlugin($pluginId) {
	$plugins = array();

	$log = App_Log::getInstance(IDENT);
	$db = Zend_Registry::get('oldDb');

	$sql = $db->select()
		->from('special_plugin_profile_items')
		->where('profile_id = ?', $pluginId);

	try {
		$log->debug($sql->__toString());
		$stmt = $sql->query();
		$results = $stmt->fetchAll();

		if (empty($results)) {
			return array();
		}

		foreach($results as $plugin) {
			switch($plugin['plugin_type']) {
				case 'all':
					return array('all' => array(0 => 'yes'));
				case 'fam':
					$plugins['family'][] = trim($plugin['plugin']);
					break;
				case 'sev':
					$enumerated = _enumerateSeverity($plugin['plugin']);

					if (empty($plugins['individual_plugin'])) {
						$plugins['individual_plugin'] = $enumerated;
					} else {
						foreach($enumerated as $id => $status) {
							$plugins['individual_plugin'][$id] = 'on';
						}
					}

					unset($enumerated);
					break;
				case 'plu':
					$id = $plugin['plugin'];
					$plugins['individual_plugin'][$id] = 'on';
					break;
				default:
					$log->err(sprintf('Unknown plugin type "%s" found in special plugin', $plugin['plugin_type']));
					break;
			}
		}

		return $plugins;
	} catch (Exception $error) {
		$log->err($error->getMessage());
		throw $error;
	}
}

/**
* Creates a policy formatted for nessquik 2.6 out of 2.5 source
*
* @return Audit_Policy
*/
function _createPolicy($params) {
	$included = array();
	$preferences = array();

	$config = Ini_Config::getInstance();
	$log = App_Log::getInstance(IDENT);
	$filter = new Zend_Filter_Boolean(Zend_Filter_Boolean::ALL);
	$pluginMap = new Audit_Policy_Preferences_Plugin;
	$scanner = new Audit_Server($config->vscan->default);
	$scannerLog = App_Log::getInstance(get_class($scanner));
	$scanner->adapter->setLogger($scannerLog);
	$scanner->adapter->setLogin($scanner->adapter->getUsername(), $scanner->adapter->getPassword());

	$account = Zend_Registry::get('account');
	$accountId = $account->id;

	$policyId = Audit_Policy_Util::create();
	if ($policyId === false) {
		throw new Zend_Controller_Action_Exception('Could not create the new audit policy');
	}

	try {
		$policy = new Audit_Policy($policyId);
		$permission = new Permissions;

		$permission = $permission->get('Policy', $policyId);
		$result = $account->acl->allow($permission[0]['permission_id']);

		$policyOptions = array(
			'policy_name' => $policy->id,
			'policy_shared' => 1
		);
		$policyPreferences = array();

		$policy->name = $params['preference']['policy_name'];
		$policy->created = new Zend_Date;

		if (empty($params['plugin_selection'])) {
			throw new Exception('You did not specify any plugins to include in the scan');
		}

		$log->debug('Iterating over plugin_selection and including plugins in array');
		foreach($params['plugin_selection'] as $type => $targets) {
			foreach($targets as $key => $target) {
				if ($target != "null") {
					if ($target == 'on') {
						$included[$type][] = $key;
					} else {
						$included[$type][] = $target;
					}
				}
			}
		}

		$log->debug('Fetching plugin preferences from server');
		$tmp = $scanner->adapter->pluginPreferences();
		if (!empty($tmp)) {
			foreach($tmp as $preference) {
				$fullName = $preference['fullName'];
				$preferences[$fullName] = trim($preference['preferenceValues']);
			}
		}
		unset($tmp);

		$log->debug('Fetching server preferences from server');
		$tmp = $scanner->adapter->serverPreferences();
		if (!empty($tmp)) {
			foreach($tmp as $preference) {
				$name = $preference['name'];

				// Plugins are determined at run time so exclude them here
				if (strpos($name, 'plugin_selection.') === false) {
					$preferences[$name] = trim($preference['value']);
				}
			}
		}
		unset($tmp);

		if (isset($params['preference']['port_range'])) {
			$log->debug('Mapping port range to preference supported by Nessus');
			$preferences['port_range'] = $params['preference']['port_range'];
		} else {
			$preferences['port_range'] = 'default';
		}

		if ($filter->filter($params['preference']['ping_host_first'])) {
			$log->debug('Mapping ping_host_first to preference supported by Nessus');
			$mapped = $pluginMap->map('ping-remote-host.do-icmp-ping');
			$preferences[$mapped] = 'on';
		}

		$log->debug('Setting plugin selection');
		$policy->setPluginSelection($included);

		$log->debug('Setting preferences for policy');
		$policy->setPreferences($preferences);

		$log->debug('Saving policy');
		$result = $policy->update();

		$log->debug('Policy saved successfully');
		return $policy;
	} catch (Exception $error) {
		$log->err($error->getMessage());
		throw new Exception($error->getMessage());
	}
}

function _createAudit($params) {
	$config = Ini_Config::getInstance();
	$log = App_Log::getInstance(IDENT);
	$filter = new Zend_Filter_Boolean(Zend_Filter_Boolean::ALL);

	$included = array();

	$account = Zend_Registry::get('account');
	$accountId = $account->id;

	$auditId = Audit_Util::create();
	if ($auditId === false) {
		throw new Zend_Controller_Action_Exception('Could not create the new audit');
	}

	try {
		$audit = new Audit($auditId);
		$permission = new Permissions;

		$permission = $permission->get('Audit', $auditId);
		$result = $account->acl->allow($permission[0]['permission_id']);
		$audit->status = 'N';
		$audit->doc->accountId = $accountId;

		if ($account->policy->hasPolicy($params['policyId'])) {
			$audit->policy = $params['policyId'];
		} else {
			throw new Zend_Controller_Action_Exception('You do not have permission to use this policy');
		}

		$audit->name = $params['name'];
		$audit->scanner = $params['scanner'];
		$audit->created = new Zend_Date;
		$audit->scheduling = $filter->filter($params['scheduling']);

		$audit->setSchedule($params);
		$audit->setNotification($params);

		if (empty($params['included'])) {
			throw new Exception_NoTargets('You need to include at least one target to scan');
		} else {
			foreach($params['included'] as $type => $targets) {
				if (empty($targets)) {
					$account = new Account($audit->accountId);
					$included = array();
					$log->err(sprintf('Weird: No audit targets were found in the imported policy. Account with audit was %s', $account->username));
				} else {
					foreach($targets as $target) {
						if ($target != "null") {
							$included[$type][] = $target;
						}
					}
				}
			}
		}

		$audit->setInclude($included);

		$result = $audit->update();
		return true;
	} catch (Exception_NoTargets $error) {
		$log->info('No targets were found. The audit will not be imported.');
		throw new Exception_NoTargets($error->getMessage());
	} catch (Exception $error) {
		$log->err($error->getMessage());
		throw new Exception($error->getMessage());
	}
}

?>
