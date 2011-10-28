<?php

/**
* @author Tim Rupp
*/
class Maintenance_Plugin_UpdateApiMethods extends Maintenance_Plugin_Abstract {
	const IDENT = __CLASS__;

	/**
	* @throws Maintenance_Plugin_Exception
	*/
	public function dispatch(Maintenance_Request_Abstract $request) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);
		$api = Ini_Api::getInstance();
		$server = new Zend_XmlRpc_Server();
		$result = array();

		$log->debug('Notified of dispatch; performing task');

		foreach($api->classes as $namespace => $api) {
			$server->setClass($api->class, $namespace);
		}

		$functions = $server->getFunctions();

		foreach($functions as $method => $val) {
			try {
				$sql = $db->select()
					->from('permissions_api', array('id'))
					->where('resource = ?', $method);

				$log->debug(sprintf('Checking to see if the API permission "%s" already exists', $method));
				$log->debug($sql->__toString());

				$stmt = $sql->query();

				if (count($stmt->fetchAll()) > 0) {
					$log->debug('Permission exists; skipping');
					continue;
				} else {
					$log->debug('Permission does not exist; adding');

					$data = array(
						'resource' => $method
					);
					$db->insert('permissions_api', $data);
				}
			} catch (Exception $error) {
				throw new Maintenance_Plugin_Exception($error->getMessage());
			}
		}
	}
}

?>
