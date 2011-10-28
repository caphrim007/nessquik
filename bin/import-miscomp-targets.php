<?php

set_time_limit(0);

if (!defined('_ABSPATH')) {
	define('_ABSPATH', dirname(dirname(__FILE__)));
}

if (!defined('IDENT')) {
	define('IDENT', 'ImportMiscompTargets');
}

require _ABSPATH.'/lib/Autoload.php';

$log	= App_Log::getInstance(IDENT);
$sysconf = Ini_Config::getInstance();
$config	= Ini_Maintenance::getInstance();
$cg 	= new Zend_Console_Getopt(
	array(
		'help'=> 'Display this help and exit',
		'run|r'	=> 'Run maintenance',
		'account|a=s' => 'The account name to import Miscomp settings for. If not specified, all accounts will be imported',
	)
);
$run = false;
$account = null;

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

if (isset($opts->a)) {
	$account = $opts->a;
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
	if ($account === null) {
		$controller = Maintenance_Engine::getInstance();
		$plugin = new Maintenance_Plugin_Fnal_SeedPermissionsFromMiscomp;
		$controller->registerPlugin($plugin);
		$controller->considerCron(false);
		$controller->dispatch();
	} else {
		$accountId = Account_Util::getId($account);

		if ($accountId == 0) {
			throw new Exception('Could not find an account ID for the account name you specified');
		} else {
			$account = new Account($accountId);
		}

		$task = new Maintenance_Plugin_Fnal_SeedPermissionsFromMiscomp;
		$task->dispatchSingle($account);
	}
} catch (Exception $error) {
	echo $error->getMessage()."\n";
	$log->err($error->getMessage());
}

function usage($error) {
	echo sprintf("\n%s\n", $error->getUsageMessage());
}

?>
