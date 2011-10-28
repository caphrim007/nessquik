<?php

set_time_limit(0);

if (!defined('_ABSPATH')) {
	define('_ABSPATH', dirname(dirname(__FILE__)));
}

if (!defined('IDENT')) {
	define('IDENT', 'Maintenance');
}

require _ABSPATH.'/lib/Autoload.php';

$log	= App_Log::getInstance(IDENT);
$sysconf = Ini_Config::getInstance();
$config	= Ini_Maintenance::getInstance();
$filter = new Zend_Filter_Boolean(Zend_Filter_Boolean::ALL);
$cg 	= new Zend_Console_Getopt(
	array(
		'help|h'=> 'Display this help and exit',
		'run|r'	=> 'Run maintenance',
		'task|t=s' => 'Only run a specific task or list of tasks. Can be task class names or file names',
		'include-task|i=s' => 'Include site specific task or list of tasks',
		'exclude-task|e=s' => 'Exclude site specific task or list of tasks',
		'without-cron|C' => 'Ignore the cron policies defined for maintenance tasks',
		'without-lock|L' => 'Do not lock the maintenance script between invocations',
		'without-global|G' => 'Do not use a global lock. Instead each task will use their own lock',
	)
);
$run = false;
$considerCron = true;
$considerLock = true;
$globalLock = true;
$task = null;
$includeTask = null;
$excludeTask = null;
$params = array();
$processes = array();
$totalFork = 0;
$forbidden = array('Abstract', 'Broker', 'Exception', 'Test');
$lockFile = null;

try {
	$opts = $cg->parse();
} catch (Zend_Console_Getopt_Exception $e) {
	usage($e);
	exit;
}

if (isset($opts->h)) {
	usage($opts);
	exit;
}

if (isset($opts->r)) {
	$run = true;
	$params['run'] = true;
} else {
	$params['run'] = false;
}

if (isset($opts->G)) {
	$globalLock = false;
	$params['globalLock'] = false;
} else{
	$params['globalLock'] = true;
}

if (isset($opts->C)) {
	$considerCron = false;
	$params['considerCron'] = false;
} else{
	$params['considerCron'] = true;
}

if (isset($opts->L)) {
	$considerLock = false;
	$params['considerLock'] = false;
} else {
	$params['considerLock'] = true;
}

if (isset($opts->t)) {
	$tasks = $opts->t;
	$task = parseTasks($tasks);
	$params['task'] = $task;
	unset($tasks);
}

if (isset($opts->i)) {
	$includeTasks = $opts->i;
	$includeTask = parseTasks($includeTasks);
	$params['includeTask'] = $includeTask;
	unset($includeTasks);
}

if (isset($opts->e)) {
	$excludeTasks = $opts->e;
	$excludeTask = parseTasks($excludeTasks);
	$params['excludeTask'] = $excludeTask;
	unset($excludeTasks);
}

if ($run === false) {
	usage($opts);
	exit;
}

if ($considerLock === true) {
	$log->debug('Asked to use cron lock');

	if ($globalLock === true) {
		$log->debug('Using global lock file');
		$pid = Cron::lock();
		if ($pid === false) {
			exit;
		}
	}
} else {
	$log->debug('Not locking file because --without-lock was specified');
}

$controller = Maintenance_Engine::getInstance();
$controller->setParams($params);
$controller->considerCron($considerCron);

if ($filter->filter($sysconf->misc->firstboot)) {
	$log->info('System has not been set-up yet. Firstboot flag still set in config file');
	exit;
}

if (!is_null($task)) {
	$pluginList = array();
	$tasks = explode(',', $task);
	unset($task);

	foreach ($tasks as $task) {
		if ($controller->hasPlugin($task)) {
			continue;
		} else {
			$controller->registerPlugin(new $task);
			if ($globalLock === false && $considerLock === true) {
				$pluginList[] = $task;
				$log->debug(sprintf('Using task lock file %s', $task));
				$pid = Cron::lock($task);
				if ($pid === false) {
					$controller->unregisterPlugin($task);
				}
			}
		}
	}

	if (count($controller->getPlugins()) > 0) {
		$controller->dispatch();
	} else {
		$log->debug('After enumerating plugins, no plugins are ready to run');
		exit;
	}

	if ($considerLock === true && $globalLock === true) {
		$log->debug('Asked to release global cron lock');
		Cron::unlock();
	} else if ($considerLock === true && $globalLock === false) {
		foreach($pluginList as $task) {
			$log->debug(sprintf('Asked to release %s cron lock', $task));
			Cron::unlock($task);
		}
	}
	exit;
}

// Register maintenance plugins provided in the standard distribution
if (is_dir($config->plugins->directory->system)) {
	$dir = new DirectoryIterator($config->plugins->directory->system);
	foreach($dir as $file ) {
		if(!$file->isDot() && !$file->isDir()) {
			$filename = $file->getPathname();
			if (in_array(basename($filename,'.php'), $forbidden)) {
				continue;
			}

			registerClass($controller, $filename);
		}
	}
}

// Register the include path for site specific plugins
if (is_dir($config->plugins->single->path)) {
	appendToPath($config->plugins->single->path);
}

// Register site specific directory of plugins
if (is_string($config->plugins->directory->user)) {
	$userDir = array($config->plugins->directory->user);
} else {
	$userDir = $config->plugins->directory->user->toArray();
}

foreach($userDir as $directory) {
	if (is_dir($directory)) {
		$dir = new DirectoryIterator($directory);
		foreach($dir as $file ) {
			if(!$file->isDot() && !$file->isDir()) {
				$filename = $file->getPathname();
				if (in_array(basename($filename,'.php'), $forbidden)) {
					continue;
				}

				registerClass($controller, $filename);
			}
		}
	}
}

try {
	$pluginList = array();
	$single = $config->plugins->single->toArray();

	// Register individual site specific plugins
	if (!empty($single['register'])) {
		if (is_array($single['register'])) {
			$tasks = $single['register'];
		} else {
			$tasks = array($single['register']);
		}

		foreach($tasks as $task) {
			if ($controller->hasPlugin($task)) {
				continue;
			} else {
				$controller->registerPlugin(new $task);
			}
		}
	}

	// Un-register any plugins that the site owner doesnt want to run
	if (!empty($single['unregister'])) {
		if (is_array($single['unregister'])) {
			$tasks = $single['unregister'];
		} else {
			$tasks = array($single['unregister']);
		}

		foreach($tasks as $task) {
			if (!$controller->hasPlugin($task)) {
				continue;
			} else {
				$controller->unregisterPlugin($task);
			}
		}
	}

	// Include tasks specified on the command line
	if (!is_null($includeTask)) {
		$tasks = explode(',', $includeTask);
		unset($includeTask);

		foreach ($tasks as $task) {
			if ($controller->hasPlugin($task)) {
				continue;
			} else {
				$controller->registerPlugin(new $task);
			}
		}
	}

	// Exclude tasks specified on the command line
	if (!is_null($excludeTask)) {
		$tasks = explode(',', $excludeTask);
		unset($excludeTask);

		foreach ($tasks as $task) {
			if (!$controller->hasPlugin($task)) {
				continue;
			} else {
				$controller->unregisterPlugin(new $task);
			}
		}
	}

	$plugins = $controller->getPlugins();
	foreach($plugins as $plugin) {
		if (!pluginStateExists($plugin)) {
			createPluginState($plugin);
		}
	}

	if ($globalLock === false) {
		$plugins = $controller->getPlugins();
		foreach ($plugins as $plugin) {
			$task = get_class($plugin);
			$pluginList[] = $task;
			$log->debug(sprintf('Using task lock file %s', $task));
			$pid = Cron::lock($task);
			if ($pid === false) {
				$controller->unregisterPlugin($task);
			}
		}
	}

	// Dispatch execution of all registered plugins
	if (count($controller->getPlugins()) > 0) {
		$controller->dispatch();
	} else {
		$log->debug('After enumerating plugins, no plugins are ready to run');
		exit;
	}

	if ($considerLock === true && $globalLock === true) {
		$log->debug('Asked to release global cron lock');
		Cron::unlock();
	} else if ($considerLock === true && $globalLock === false) {
		foreach($pluginList as $task) {
			$log->debug(sprintf('Asked to release %s cron lock', $task));
			Cron::unlock($task);
		}
	}
} catch (Exception $error) {
	$log->err($error->getMessage());
}

function usage($error) {
	echo sprintf("\n%s\n", $error->getUsageMessage());
}

function pluginStateExists($plugin) {
	$config = Ini_Config::getInstance();
	$log = App_Log::getInstance(IDENT);
	$db = App_Db::getInstance($config->database->default);

	$class = get_class($plugin);

	$sql = $db->select()
		->from('maintenance_state')
		->where('task = ?', $class);

	try {
		$log->debug($sql->__toString());
		$stmt = $sql->query();
		if ($stmt->rowCount() > 0) {
			return true;
		} else {
			return false;
		}
	} catch (Exception $error) {
		throw new Maintenance_Exception($error->getMessage());
	}
}

function createPluginState($plugin) {
	$config = Ini_Config::getInstance();
	$log = App_Log::getInstance(IDENT);
	$db = App_Db::getInstance($config->database->default);

	$sql = array(
		'insert' => 'INSERT INTO %s (%s) VALUES (%s)'
	);

	$class = get_class($plugin);

	try {
		$query = sprintf($sql['insert'],
			$db->quoteIdentifier('maintenance_state'),
			$db->quoteIdentifier('task'),
			$db->quote($class)
		);

		$log->debug($query);

		$db->query($query);
	} catch (Exception $error) {
		throw new Maintenance_Exception($error->getMessage());
	}
}

function parseTasks($tasks) {
	$tasks = explode(',', $tasks);

	foreach ($tasks as $task) {
		if (!file_exists($task)) {
			$result[] = $task;
			continue;
		}

		$php = file_get_contents($task);
		$tokens = token_get_all($php);
		$class_token = false;
		foreach ($tokens as $token) {
			if (is_array($token)) {
				if ($token[0] == T_CLASS) {
					$class_token = true;
				} else if ($class_token && $token[0] == T_STRING) {
					$result[] = $token[1];
					$class_token = false;
				}
			}
		}
	}

	return implode(',', $result);
}

?>
