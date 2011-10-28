<?php

set_time_limit(0);

if (!defined('_ABSPATH')) {
	define('_ABSPATH', dirname(dirname(__FILE__)));
}

if (!defined('IDENT')) {
	define('IDENT', 'ImportWhitelist');
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
		'dbname|d=s' => 'The name of the nessquik 2.5 database to connect to',
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
	$permissions = new Permissions;

	$sql = $oldDb->select()
		->from('whitelist')
		->order('username ASC');

	$log->debug($sql->__toString());

	$stmt = $sql->query();
	$result = $stmt->fetchAll();

	if (empty($result)) {
		throw new Exception('No whitelist entries were found in the nessquik 2.5 database you specified');
	}

	foreach($result as $account) {
		$username = $account['username'];
		$entry = $account['listed_entry'];

		if (!Account_Util::exists($username)) {
			$log->info(sprintf('Account "%s" does not exist in the new database', $username));
		} else {
			$accountId = Account_Util::getId($username);
			$account = new Account($accountId);

			if (Ip::isIpAddress($entry) || Ip::isRange($entry) || Ip::isCidr($entry)) {
				if ($permissions->exists('NetworkTarget', $entry)) {
					$log->info(sprintf('The network target "%s" already exists', $entry));
				} else {
					$result = $permissions->add('NetworkTarget', $entry);
					if ($result === true) {
						$log->info(sprintf('Successfully added the network target "%s"', $entry));
					} else {
						$log->err(sprintf('Failed to add the network target', $entry));
					}
				}

				$permissionId = $permissions->getId('NetworkTarget', $entry);
				$account->acl->allow($permissionId);
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

?>
