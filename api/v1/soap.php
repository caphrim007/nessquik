<?php

// Used for including files
if (!defined("_ABSPATH")) {
	define("_ABSPATH", dirname(dirname(__FILE__)));
}

require _ABSPATH.'/lib/Autoload.php';

$api		= Ini_Api::getInstance();
$config 	= Ini_Config::getInstance();
$log 		= App_Log::getInstance("XmlRpcApi", $config->debug->log->xmlrpc);
$rpc		= App_Log::getInstance("XmlRpcApi", $config->debug->log->usage);
$requestor      = iv('REMOTE_ADDR', 'SE');
$methods        = array();

$log->debug('Constructing class');

$server = new Zend_XmlRpc_Server();
if ($config->debug->log->mask == "debug") {
	// Note that this will cause normal PHP errors to be exposed via the API
	Zend_XmlRpc_Server_Fault::attachFaultException('Exception');
}

if (!Zend_XmlRpc_Server_Cache::get($config->datasource->cache->xmlrpc, $server) || !$config->cache->xmlrpc) {
	foreach($api->classes as $namespace => $api) {
		$log->debug(sprintf('Registering class "%s" which will use namespace "%s"', $api->class, $namespace));
		$server->setClass($api->class, $namespace);
	}

	if ($config->cache->xmlrpc == true) {
		Zend_XmlRpc_Server_Cache::save($config->datasource->cache->xmlrpc, $server);
	}
} else {
	$log->debug('Using XML-RPC cache');
}

$req = new Zend_XmlRpc_Request_Http();
$log->debug(sprintf('%s requested the method "%s"', $requestor, $req->getMethod()));
$rpc->debug(sprintf('%s %s', $requestor, $req->getMethod()));

try {
	echo $server->handle($req);
	$log->debug('You got served');
} catch (Exception $error) {
	$log->err($error->getMessage());
	throw new Api_Exception($error->getMessage());
}

?>
