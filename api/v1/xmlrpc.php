<?php

// Used for including files
if (!defined('_ABSPATH')) {
	define('_ABSPATH', dirname(dirname(dirname(__FILE__))));
}

if (!defined('IDENT')) {
	define('IDENT', 'XmlRpcApi');
}

require _ABSPATH.'/lib/Autoload.php';

$api		= Ini_Api::getInstance();
$config 	= Ini_Config::getInstance();
$log 		= App_Log::getInstance(IDENT, $config->debug->log->xmlrpc);
$messages	= App_Log::getInstance(IDENT, $config->debug->log->messages);
$rpc		= App_Log::getInstance(IDENT, $config->debug->log->usage);
$requestor	= $_SERVER['REMOTE_ADDR'];
$methods	= array();

$log->debug('Constructing class');

if ($config->debug->dependencies->track) {
	if (is_writable($config->debug->dependencies->path)) {
		$messages->debug(sprintf('Tracking dependencies to "%s"', $config->debug->dependencies->path));
		$included = new Zend_Debug_Include_Manager();
		$included->setAdapter(new Zend_Debug_Include_Adapter_File());
		$included->setOutputDir($config->debug->dependencies->path);
	} else {
		$messages->err('Dependencies path is not writable. Will not track dependencies');
	}
}

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
	$response = $server->handle($req);
	$log->debug(sprintf('XML-RPC response: %s', $response->__toString()));
	echo $response;
} catch (Exception $error) {
	$log->err($error->getMessage());
	throw new Api_Exception($error->getMessage());
}

?>
