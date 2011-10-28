<?php

/**
* @author Tim Rupp
*/
class Maintenance_Plugin_UpdateNaslNames extends Maintenance_Plugin_Abstract {
	const IDENT = __CLASS__;

	public function dispatch(Maintenance_Request_Abstract $request) {
		$log = App_Log::getInstance(self::IDENT);

		$nasl	= array();
		$filename 	= '';
		$plugin_pairs	= array();
		$pattern	= '/[0-9]+/';
		$id		= 0;
		$script_name	= '';

		$log->debug('Notified of dispatch; performing task');

		try {
			$adapter = $this->_getAdapterCredentials();
			if (empty($adapter)) {
				throw new Maintenance_Plugin_Exception('No scanner is configured to be used for updating plugins');
			}

			$pluginDir = $adapter->pluginDir;

			$log->debug('Notified of dispatch; performing task');
			$log->debug(sprintf('Checking to see if "%s" is a directory', $pluginDir));

			if (is_dir($pluginDir)) {
				$log->debug(sprintf('Reading "%s" for plugins', $pluginDir));

				if (!is_readable($pluginDir)) {
					throw new Maintenance_Plugin_Exception('Could not read the Nessus plugins directory');
				}

				$dir = new DirectoryIterator($pluginDir);
				foreach($dir as $file ) {
					if(!$file->isDot() && !$file->isDir()) {
						$nasl[] = $file->getPathname();
					}
				}
			}

			$log->debug('Finding script IDs in NASL files');
			foreach($nasl as $filename) {
				$fh = fopen($filename, 'r');
				$found = false;

				while(!feof($fh)) {
					$matches = array();
					$line = fgets($fh,4096);

					if(strpos($line, "script_id(") === false) {
						continue;
					} else {
						$line = trim($line);
						preg_match($pattern,$line,$matches);
						if (count($matches) > 0) {
							$this->_updateNaslName($matches[0], $filename);
							$found = true;
							break;
						}
					}

					if ($found === true) {
						break;
					}
				}

				fclose($fh);
			}
		} catch (Exception $error) {
			$log->err($error->getMessage());
			throw new Maintenance_Plugin_Exception($error->getMessage());
		}
	}

	protected function _updateNaslName($id, $script_name) {
		$config = Ini_Config::getInstance();

		try {
			$plugin = new Audit_Plugins_Plugin($id);
			$plugin->script	= $script_name;

			$plugin->update();
		} catch (Exception $error) {
			throw new Maintenance_Plugin_Exception($error->getMessage());
		}
	}

	protected function _getAdapterCredentials() {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('scanners')
			->where('for_update = ?', true);

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			$result = $stmt->fetchAll();

			if (empty($result)) {
				return array();
			} else {
				$result = $result[0];
				$options = array(
					'adapter' => $result['adapter'],
					'params' => array(
						'host' => $result['host'],
						'port' => $result['port'],
						'username' => $result['username'],
						'password' => $result['password']
					),
					'pluginDir' => $result['plugin_dir']
				);

				return new Zend_Config($options);
			}
		} catch (Exception $error) {
			throw new Maintenance_Plugin_Exception($error->getMessage());
		}
	}
}

?>
