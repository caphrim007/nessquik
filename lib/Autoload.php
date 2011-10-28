<?php

if (!defined('_ABSPATH')) {
	define('_ABSPATH', dirname(dirname(__FILE__)));
}

require_once _ABSPATH.'/lib/Zend/Loader.php';

if (!function_exists('__autoload')) {
	function __autoload($class) {
		appendToPath(_ABSPATH.'/lib/');
		Zend_Loader::loadClass($class);
	}
}

function appendToPath($path) {
	if (is_array($path)) {
		$toInclude = $path;
	} else {
		$toInclude = array(
			$path
		);
	}

	foreach($toInclude as $path) {
		if (!file_exists($path) OR (file_exists($path) && filetype($path) !== 'dir')) {
			trigger_error("Include path '{$path}' not exists", E_USER_WARNING);
			continue;
		}
       
		$paths = explode(PATH_SEPARATOR, get_include_path());
       
		if (array_search($path, $paths) === false) {
			array_push($paths, $path);
		}

		set_include_path(implode(PATH_SEPARATOR, $paths));
	}
}

/**
* Code borrowed from
*
*	http://stackoverflow.com/questions/928928/determining-what-classes-are-defined-in-a-php-class-file
*/
function registerClass($controller, $filename) {
	$php = file_get_contents($filename);
	$tokens = token_get_all($php);
	$class_token = false;
	foreach ($tokens as $token) {
		if (is_array($token)) {
			if ($token[0] == T_CLASS) {
				$class_token = true;
			} else if ($class_token && $token[0] == T_STRING) {
				if (!$controller->hasPlugin($token[1])) {
					$controller->registerPlugin(new $token[1]);
				}
				$class_token = false;
			}
		}
	}
}

?>
