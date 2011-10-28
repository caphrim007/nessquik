<?php

if (!@$argc) {
	die ("<p>This script can only be run from command line");
}

define('_NEW_ABSPATH', dirname(dirname(dirname(__FILE__))));

if (is_writable(_NEW_ABSPATH.'/confs/')) {
	$fh = fopen(_NEW_ABSPATH.'/confs/config-inc.new.php', 'w');
} else {
	die ("You don't have permission to write to the confs directory\n\n\t"._NEW_ABSPATH."/confs/\n");
}

if (!file_exists(_NEW_ABSPATH.'/confs/config-inc.php')) {
	die ("Your old configuration file was not found.\n\n"
	. "I was looking for a config-inc.php file in the directory " . _NEW_ABSPATH . "\n");
} else {
	require(_NEW_ABSPATH.'/confs/config-inc.php');
}

/**
* Write the new configuration file line by line
*/

fwrite($fh, "<?php\n");
fwrite($fh, "\n");
fwrite($fh, "// Needed for all web->database operations\n");
fwrite($fh, 'define("_DBUSER", "' . _DBUSER . '");' . "\n");
fwrite($fh, 'define("_DBPASS", "' . _DBPASS . '");' . "\n");
fwrite($fh, 'define("_DBSERVER", "' . _DBSERVER . '");' . "\n");
fwrite($fh, 'define("_DBPORT", ' . _DBPORT . ');' . "\n");
fwrite($fh, 'define("_DBUSE", "' . _DBUSE . '");' . "\n");
fwrite($fh, 'define("_CONNECT_TYPE", "");' . "\n");
fwrite($fh, "\n");
fwrite($fh, "// Used as a means of linking to plugins\n");
fwrite($fh, '$links = array(' . "\n");
fwrite($fh, "\t'nessus'\t\t=> " . '"http://www.nessus.org/plugins/index.php?view=single&id=",' . "\n");
fwrite($fh, ");");
fwrite($fh, "\n");
fwrite($fh, "// Needed for updating the plugins table\n");
fwrite($fh, 'define("_NESSUS_SERVER", "' . _NESSUS_SERVER . '");' . "\n");
fwrite($fh, 'define("_NESSUS_PORT", ' . _NESSUS_PORT . ');' . "\n");
fwrite($fh, 'define("_NESSUS_USER", "' . _NESSUS_USER . '");' . "\n");
fwrite($fh, 'define("_NESSUS_PASS", "' . _NESSUS_PASS . '");' . "\n");
fwrite($fh, 'define("_NESSUS_CMD", "' . _NESSUS_CMD . '");' . "\n");
fwrite($fh, "\n");
fwrite($fh, "// Used to update the nasl_names table\n");
fwrite($fh, 'define("_NESSUS_PLUG_DIR", "' . _NESSUS_PLUG_DIR . '");' . "\n");
fwrite($fh, "\n");
fwrite($fh, "// Used when making the nessusrc file. If you dont have one then leave it blank\n");

if (defined('_TRUSTED_CA')) {
	fwrite($fh, 'define("_TRUSTED_CA", "' . _TRUSTED_CA . '");' . "\n");
} else {
	fwrite($fh, 'define("_TRUSTED_CA", "/usr/local/com/nessus/CA/cacert.pem");' . "\n");
}
	
fwrite($fh, "\n");
fwrite($fh, "// Used for including files\n");
fwrite($fh, 'if (!defined("_ABSPATH")) {' . "\n");
fwrite($fh, "\t" . 'define("_ABSPATH", dirname(dirname(__FILE__)));' . "\n");
fwrite($fh, "}\n");
fwrite($fh, "\n");
fwrite($fh, "// Mail server settings\n");

if (defined('_SMTP_SERVER')) {
	fwrite($fh, 'define("_SMTP_SERVER", "' . _SMTP_SERVER . '");' . "\n");
} else {
	fwrite($fh, 'define("_SMTP_SERVER", "localhost");' . "\n");
}

if (defined('_SMTP_AUTH')) {
	if (_SMTP_AUTH) {
		fwrite($fh, 'define("_SMTP_AUTH", true);' . "\n");
	} else {
		fwrite($fh, 'define("_SMTP_AUTH", false);' . "\n");
	}
} else {
	fwrite($fh, 'define("_SMTP_AUTH", false);' . "\n");
}

if (defined('_SMTP_FROM')) {
	fwrite($fh, 'define("_SMTP_FROM", "' . _SMTP_FROM . '");' . "\n");
} else {
	fwrite($fh, 'define("_SMTP_FROM", "nessquik@localhost");' . "\n");
}

if (defined('_SMTP_FROM_NAME')) {
	fwrite($fh, 'define("_SMTP_FROM_NAME", "' . _SMTP_FROM_NAME . '");' . "\n");
} else {
	fwrite($fh, 'define("_SMTP_FROM_NAME", "nessquik");' . "\n");
}

fwrite($fh, "\n");
fwrite($fh, "// Version of nessquik\n");
fwrite($fh, 'define("_VERSION", "2.5");' . "\n");
fwrite($fh, "\n");
fwrite($fh, "// Turn debugging on or off. This affects the errors that are displayed\n");
fwrite($fh, 'define("_DEBUG", false);' . "\n");
fwrite($fh, "\n");
fwrite($fh, "// Release of nessquik\n");
fwrite($fh, 'define("_RELEASE", "general");' . "\n");
fwrite($fh, "\n");
fwrite($fh, "// Define session timeout value in seconds\n");

if (defined('_TIMEOUT')) {
	fwrite($fh, 'define("_TIMEOUT", ' . _TIMEOUT . ');' . "\n");
} else {
	fwrite($fh, 'define("_TIMEOUT", 86400);' . "\n");
}

fwrite($fh, "\n");
fwrite($fh, "// Whether or not nessquik should check to make sure it is being run over HTTPS\n");
fwrite($fh, 'define("_CHECK_SECURE", true);' . "\n");
fwrite($fh, "\n");
fwrite($fh, "// Primary email will be sent to this email address\n");

if (defined('_RECIPIENT')) {
	fwrite($fh, 'define("_RECIPIENT", "' . _RECIPIENT . '");' . "\n");
} else {
	fwrite($fh, 'define("_RECIPIENT", "root@localhost");' . "\n");
}

fwrite($fh, "\n");
fwrite($fh, "// Needed to support the packaged PEAR\n");
fwrite($fh, 'ini_set("include_path", ".:"._ABSPATH."/lib/pear");' . "\n");
fwrite($fh, "\n");
fwrite($fh, '$allowed_editors = array(' . "\n");
fwrite($fh, "\t'admin'\n");
fwrite($fh, ");");
fwrite($fh, "\n");
fwrite($fh, '?>');

rename(_NEW_ABSPATH.'/confs/config-inc.php', _NEW_ABSPATH.'/confs/config-inc.old.php');

if (!file_exists(_NEW_ABSPATH.'/confs/config-inc.old.php')) {
	die ("Old config was not backed up successfully. Exiting before we end up removing the old config\n\n");
}

rename(_NEW_ABSPATH.'/confs/config-inc.new.php', _NEW_ABSPATH.'/confs/config-inc.php');

if (!file_exists(_NEW_ABSPATH.'/confs/config-inc.php')) {
	die ("New config file couldn't be created correctly.");
}

unlink(_NEW_ABSPATH.'/confs/config-inc.old.php');

echo "Config file updated. Old config file removed\n\n";

?>
