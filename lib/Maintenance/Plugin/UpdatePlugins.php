<?php

/**
* @author Tim Rupp
*/
class Maintenance_Plugin_UpdatePlugins extends Maintenance_Plugin_Abstract {
	const IDENT = __CLASS__;

	public function dispatch(Maintenance_Request_Abstract $request) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$checkpoint = 0;
		$total = 0;
		$time_start = microtime(true);

		$log->debug('Notified of dispatch; performing task');

		try {
			$server = new Audit_Server($config->vscan->default);
			$pluginFamilies = $server->adapter->listPlugins();
			$totalPlugins = $this->_countPlugins($pluginFamilies);

			$log->debug(sprintf('Processing %s plugins', $totalPlugins));

			foreach($pluginFamilies as $family) {
				$pluginShort = $server->adapter->listPluginsInFamily($family['familyName']);

				foreach($pluginShort as $shortInfo) {
					$data['id'] = $shortInfo['pluginID'];
					$data['script'] = $shortInfo['pluginFileName'];
					$data['name'] = $shortInfo['pluginName'];
					$data['family'] = $shortInfo['pluginFamily'];

					if (empty($data['id'])) {
						continue;
					}

					$pluginInfo = $server->adapter->lookupPlugin($shortInfo['pluginFileName']);
					$data = array_merge($data, $pluginInfo['pluginAttributes']);
					$data = $this->_normalizeDates($data);

					$plugin = new Plugin($data['id']);
					foreach($data as $attr => $val) {
						$plugin->$attr = $val;
					}

					try {
						$result = $plugin->create();
						$metadata = $plugin->metadata->getData();
						foreach($metadata as $attribute => $value) {
							$plugin->metadata->create($attribute, $value);
						}
					} catch (Exception $error) {
						$result = $plugin->update();
						$result = $plugin->metadata->update();
					}

					$checkpoint++;
					$total++;

					if ($checkpoint == 1000) {
						$log->debug(sprintf("Processed %s plugins so far", $total));
						$time_end = microtime(true);
						$time = round($time_end - $time_start, 5);
						$log->debug(sprintf('Spent %s seconds processing plugins', $time));

						$checkpoint = 0;
					}
				}
			}

			$log->info(sprintf("Processed a total of %s plugins", $total));
			$time_end = microtime(true);
			$time = round($time_end - $time_start, 5);
			$log->debug(sprintf('Spent %s seconds processing plugins', $time));
		} catch (Exception $error) {
			$log->err($error->getMessage());
			throw new Maintenance_Plugin_Exception($error->getMessage());
		}
	}

	protected function _normalizeDates($data) {
		$date = new Zend_Date;
		$date = $date->setTime('00:00:00', 'HH:mm:ss');
		$date = $date->setTimezone('UTC');

		$dateFields = array(
			'vuln_publication_date',
			'plugin_publication_date',
			'plugin_modification_date',
			'patch_publication_date'
		);

		foreach($dateFields as $key) {
			if (isset($data[$key])) {
				$date = $date->setDate($data[$key], 'YYYY/MM/dd');
				$data[$key] = $date->get(Zend_Date::W3C);
			}
		}

		return $data;
	}

	protected function _countPlugins($families) {
		$totalPlugins = 0;

		foreach($families as $family) {
			$totalPlugins += $family['numFamilyMembers'];
		}

		return $totalPlugins;
	}
}

?>
