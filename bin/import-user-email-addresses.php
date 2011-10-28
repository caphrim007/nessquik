<?php

set_time_limit(0);

if (!defined('_ABSPATH')) {
	define('_ABSPATH', dirname(dirname(__FILE__)));
}

if (!defined('IDENT')) {
	define('IDENT', 'ImportUserEmailAddresses');
}

require _ABSPATH.'/lib/Autoload.php';

$log	= App_Log::getInstance(IDENT);
$sysconf = Ini_Config::getInstance();
$config	= Ini_Maintenance::getInstance();
$cg 	= new Zend_Console_Getopt(
	array(
		'help'=> 'Display this help and exit',
		'run|r'	=> 'Run maintenance',
		'xmlrpc|x=s' => 'The CSTAPI XML-RPC URL to connect to'
	)
);
$run = false;
$xmlrpc = $sysconf->ws->api->cstapi->uri;
$notFound = array();

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

if (isset($opts->x)) {
	$xmlrpc = $opts->x;
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
	$rpc = new Zend_XmlRpc_Client($xmlrpc);
	$client = $rpc->getProxy();
	$db = App_Db::getInstance($sysconf->database->default);

	$sql = $db->select()
		->distinct()
		->from('accounts', array('id'));

	$log->debug($sql->__toString());

	$stmt = $sql->query();
	$result = $stmt->fetchAll();

	if (empty($result)) {
		throw new Exception('No user accounts were found in the nessquik 2.6 database');
	}

	foreach($result as $account) {
		$account = new Account($account['id']);
		$email = trim($client->acct->getEmailFromUsername($account->username));

		if (empty($email)) {
			$log->info(sprintf('The email for user "%s" was not found in Active Directory.', $account->username));
			$log->info('If this is not a local account it is possible they left the lab');
			$notFound[] = $account->username;
		} else {
			$account->doc->emailContact = array($email);
			$account->update();
		}
	}

	if (count($notFound) > 0) {
		echo "The following accounts were not found in Active Directory.\n";
		echo "If these are not local nessquik accounts, you may want to delete them\n\n";
		sort($notFound);
		foreach($notFound as $account) {
			echo sprintf("\taccount: '%s'\n", $account);
		}
		echo "\n";
	}
} catch (Exception $error) {
	echo $error->getMessage()."\n";
	$log->err($error->getMessage());
}

function usage($error) {
	echo sprintf("\n%s\n", $error->getUsageMessage());
}

?>
