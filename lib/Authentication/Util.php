<?php

/**
* @author Tim Rupp
*/
class Authentication_Util {
	const IDENT = __CLASS__;

	public static function hasAuthType($type) {
		$types = self::authTypes();
		$log = App_Log::getInstance(self::IDENT);

		if (!is_array($type)) {
			$type = array($type);
		}

		foreach($type as $check) {
			$check = str_replace('*', '.*', $check);
			$regex = sprintf('/^%s$/i', $check);
			$log->debug(sprintf('Checking auth with regex "%s" against available types', $regex));

			foreach($types as $subject) {
				if (preg_match($regex, $subject)) {
					return true;
				}
			}
		}

		return false;
	}

	public static function authTypes() {
		$auth = Ini_Authentication::getInstance();
		$types = array();

		foreach($auth->auth as $key => $val) {
			$types[] = $val->adapter;
		}

		return $types;
	}
}

?>
