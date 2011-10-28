<?php

set_time_limit(0);

if (!defined('_ABSPATH')) {
	define('_ABSPATH', dirname(dirname(__FILE__)));
}

if (!defined('IDENT')) {
	define('IDENT', 'FixMissingScannerAccounts');
}

require _ABSPATH.'/lib/Autoload.php';

$log	= App_Log::getInstance(IDENT);
$sysconf = Ini_Config::getInstance();
$config	= Ini_Maintenance::getInstance();
$cg 	= new Zend_Console_Getopt(
	array(
		'help'=> 'Display this help and exit',
		'run|r'	=> 'Run maintenance',
	)
);
$run = false;
$count = 0;

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

if ($run === false) {
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

	$sql = $db->select()
		->from('accounts', array('id'));

	$log->debug($sql->__toString());

	$stmt = $sql->query();
	$result = $stmt->fetchAll();

	if (empty($result)) {
		throw new Exception('No user accounts were found in the database');
	}

	$scanners = _getScanners();

	foreach($result as $acct) {
		$account = new Account($acct['id']);

		foreach($scanners as $scanner) {
			$users = $scanner->adapter->listUsers();
			$found = false;

			foreach($users as $user) {
				if ($user['name'] == $account->username) {
					$found = true;
				}
			}

			if ($found === false) {
				$username = trim($account->doc->serverUsername);
				$password = trim($account->doc->serverPassword);

				if (empty($username)) {
					$log->info('Username was empty');
				} else if (empty($password)) {
					$log->info(sprintf('Password for username "%s" was empty', $username));
				} else {
					$log->info(sprintf('Scanner "%s" was missing account for user "%s"', $scanner->id, $account->username));
					$scanner->adapter->addUser($account->doc->serverUsername, $account->doc->serverPassword);
				}
			}
		}
	}
} catch (Exception $error) {
	echo $error->getMessage()."\n";
	$log->err($error->getMessage());
}

function usage($error) {
	echo sprintf("\n%s\n", $error->getUsageMessage());
}

function _getScanners() {
	$config = Ini_Config::getInstance();
	$log = App_Log::getInstance(IDENT);
	$couch = new Phly_Couch($config->database->couch->params);
	$result = array();

	$view = $couch->view('audit', 'scannersInUse', array('group' => true));
	if (count($view) > 0) {
		foreach($view as $document) {
			$scanner = new Audit_Server($document->key);
			$scannerLog = App_Log::getInstance(get_class($scanner));
			$scanner->adapter->setLogger($scannerLog);
			$result[] = $scanner;
		}
	}

	return $result;
}


?>
