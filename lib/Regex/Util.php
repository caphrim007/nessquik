<?php

/**
* @author Tim Rupp
*/
class Regex_Util {
	const IDENT = __CLASS__;

	public static function exists($pattern, $application = 'account') {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('regex')
			->limit(1);

		if (is_numeric($pattern)) {
			$sql->where('id = ?', $pattern)
				->orWhere('pattern = ?', $pattern);
		} else {
			$sql->where('pattern = ?', $pattern);
		}

		$sql->where('application = ?', $application);

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			$result = $stmt->fetchAll();

			if (count($result) > 0) {
				return true;
			} else {
				return false;
			}
		} catch (Exception $error) {
			$log->err($error->getMessage());
			return false;
		}
	}

	public static function getRegexId($pattern, $application = 'account') {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('regex', array('id'))
			->where('pattern = ?', $pattern)
			->where('application = ?', $application);

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			$result = $stmt->fetchAll();

			if (count($result) > 0) {
				return $result[0]['id'];
			} else {
				return 0;
			}
		} catch (Exception $error) {
			throw new Regex_Exception($error->getMessage());
		}
	}

	/**
	* @throws Regex_Exception
	* @return integer
	*/
	public static function create($pattern = null, $description = null) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		try {
			$data = array(
				'pattern' => $pattern,
				'desc' => $description
			);

			$db->insert('regex', $data);
			return $db->lastSequenceId('regex_id_seq');
		} catch (Exception $error) {
			throw new Regex_Exception($error->getMessage());
		}
	}

	public static function testAccount($pattern) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('accounts', array('match' => 'username'))
			->where('username ~ E?', $pattern)
			->order('match ASC')
			->limit(10);

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			return $stmt->fetchAll();
		} catch (Exception $error) {
			throw new Regex_Exception($error->getMessage());
		}
	}

	public static function testUrl($pattern) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$sql = $db->select()
			->from('urls', array('match' => 'uri'))
			->where('uri ~ E?', $pattern)
			->order('match ASC')
			->limit(10);

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			return $stmt->fetchAll();
		} catch (Exception $error) {
			throw new Regex_Exception($error->getMessage());
		}
	}

	public static function getPatterns($type = null) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);
		$tmp = array();

		$sql = $db->select()
			->from('regex');

		if ($type !== null) {
			$sql->where('type = ?', $type);
		}

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			return $stmt->fetchAll();
		} catch (Exception $error) {
			throw new Regex_Exception($error->getMessage());
		}
	}

	public static function getQueuesfromPatternMatches($matches) {
		$config = Ini_Config::getInstance();
		$log = App_Log::getInstance(self::IDENT);
		$db = App_Db::getInstance($config->database->default);

		$tmp = array();
		$matches = array_values($matches);

		if (empty($matches)) {
			$log->err('The list of pattern matches sent to "getQueuesFromPatternMatches" was empty');
			return array();
		}

		$sql = $db->select()
			->from('regex_automations', null)
			->joinLeft('permissions_queue', 'regex_automations.automation_id = permissions_queue.id', null)
			->joinLeft('queue', 'permissions_queue.resource = queue.queue_id', array('queue_name'))
			->where('regex_automations.regex_id IN (?)', $matches)
			->where('queue.queue_id IS NOT NULL');

		try {
			$log->debug($sql->__toString());
			$stmt = $sql->query();
			$results = $stmt->fetchAll();
			if (empty($results)) {
				return $results;
			} else {
				foreach($results as $result) {
					$tmp[] = $result['queue_name'];
				}

				$tmp = array_values(array_unique($tmp));
				return $tmp;
			}
		} catch (Exception $error) {
			throw new Regex_Exception($error->getMessage());
		}
	}
}

?>
