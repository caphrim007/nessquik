<?php

set_time_limit(0);

if (!defined('_ABSPATH')) {
	define('_ABSPATH', dirname(dirname(__FILE__)));
}

if (!defined('IDENT')) {
	define('IDENT', 'GrantAdminRights');
}

require _ABSPATH.'/lib/Autoload.php';

$log	= App_Log::getInstance(IDENT);
$sysconf = Ini_Config::getInstance();
$config	= Ini_Maintenance::getInstance();
$cg 	= new Zend_Console_Getopt(
	array(
		'help'=> 'Display this help and exit',
		'run|r'	=> 'Run maintenance',
		'user|u=s' => 'The user that you want to grant admin privileges to',
	)
);
$run = false;
$user = null;

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

if (isset($opts->u)) {
	$user = $opts->u;
}

if ($run === false) {
	usage($opts);
	exit;
}

if ($user === null) {
	usage($opts);
	exit;
}

if ($sysconf->misc->firstboot == 1) {
	$log->info('System has not been set-up yet. Firstboot flag still set in config file');
	exit;
}

try {
	$config = Ini_Config::getInstance();
	$log = App_Log::getInstance(IDENT);
	$db = App_Db::getInstance($config->database->default);
	$permission = new Permissions;

	$accountId = Account_Util::getId($user);
	$account = new Account($accountId);

	$capabilities = getAllCapabilities();
	$targets = getAdminTargets();

	if (empty($capabilities)) {
		$log->err('No capabilities were found in the database !');
	} else  {
		foreach($capabilities as $capability) {
			$log->info(sprintf('Adding capability %s to user account', $capability['resource']));
			$result = $account->acl->allow($capability['id']);
		}
	}

	if (empty($targets)) {
		$log->err('No admin targets were found in the database !');
	} else {
		foreach($targets as $target) {
			$log->info(sprintf('Adding target access for target %s to user account', $target['resource']));
			$result = $account->acl->allow($target['id']);
		}
	}
} catch (Exception $error) {
	echo $error->getMessage()."\n";
	$log->err($error->getMessage());
}

function usage($error) {
	echo sprintf("\n%s\n", $error->getUsageMessage());
}

function getAllCapabilities() {
	$config = Ini_Config::getInstance();
	$log = App_Log::getInstance(IDENT);
	$db = App_Db::getInstance($config->database->default);

	$sql = $db->select()->from('permissions_capability');

	$stmt = $sql->query();
	return $stmt->fetchAll();
}

function getAdminTargets() {
	$config = Ini_Config::getInstance();
	$log = App_Log::getInstance(IDENT);
	$db = App_Db::getInstance($config->database->default);

	$sql = $db->select()
		->from('permissions_address')
		->where('resource = ?', '0.0.0.0/0')
		->orWhere('resource = ?', '::/0');

	$stmt = $sql->query();
	return $stmt->fetchAll();
}

?>
