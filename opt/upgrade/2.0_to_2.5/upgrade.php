<?php

define('_ABSPATH', dirname(dirname(dirname(__FILE__))));

require_once(_ABSPATH.'/confs/config-inc.php');
require_once(_ABSPATH.'/lib/functions.php');
require_once(_ABSPATH.'/db/nessquikDB.php');

$db	= nessquikDB::getInstance();

$sql = array(
	'insert_all_groups' => "INSERT INTO division_group_list (`group_name`) VALUES ('All Groups');",

	// SQL to add a new scanner
	'add_a_scanner' => "INSERT INTO scanners (`name`,`client_key`) VALUES (':1',':2');",
	'select_all_groups' => "SELECT group_id FROM division_group_list WHERE group_name='All Groups';",
	'scanner_id' => "SELECT scanner_id FROM scanners WHERE name=':1' AND client_key=':2';",
	'group_insert' => "INSERT INTO scanners_groups(`group_id`,`scanner_id`) VALUES (':1',':2');",

	// SQL to update all the scan profiles to use the new scanner
	'select_profiles' => "SELECT profile_id FROM profile_list;",
	'update_machine_scanner_id' => "UPDATE profile_settings SET scanner_id=':1' WHERE profile_id=':2';",

	// SQL to update the machine names to use new format
	'update_machine_prefix' => "UPDATE profile_machine_list SET machine=':1' WHERE row_id=':2';",
	'select_machines' => "SELECT 	pml.row_id,
					prs.username,
					pml.machine 
				FROM profile_machine_list AS pml 
				LEFT JOIN profile_settings AS prs 
				ON prs.profile_id=pml.profile_id
				ORDER BY prs.username ASC;",
);

$db->load_sql_file(_ABSPATH."/upgrade/2.0_to_2.5/sql/upgrade.sql");

// Inserts a master group called "All Groups"
$stmt01 = $db->prepare($sql['insert_all_groups']);

// Code to update machines in scanner profile
$stmt02 = $db->prepare($sql['select_machines']);
$stmt03 = $db->prepare($sql['update_machine_prefix']);

// Code to update scanner ids for all scanner profiles
$stmt04 = $db->prepare($sql['select_profiles']);
$stmt05 = $db->prepare($sql['update_machine_scanner_id']);

// Code to add a default scanner
$stmt06 = $db->prepare($sql['select_all_groups']);
$stmt07 = $db->prepare($sql['add_a_scanner']);
$stmt08 = $db->prepare($sql['scanner_id']);
$stmt09 = $db->prepare($sql['group_insert']);

echo "Updating: Database\n\n";

// Insert the "All Groups" entry
$stmt01->execute();

echo "\tDatabase updated!\n\n";
echo "Adding a default Scanner\n\n";

// And now add a default scanner
$stmt06->execute();
$scanner_name	= "localhost";
$group_id 	= $stmt06->result(0);
$client_id 	= random_string(32);

$stmt07->execute($scanner_name, $client_id);
$stmt08->execute($scanner_name, $client_id);

$scanner_id 	= $stmt08->result(0);
$stmt09->execute($group_id,$scanner_id);

echo "\tScanner added. Use the following client key\n"
. "\tin your nessquik-client config file.\n\n"
. "\t\t$client_id\n\n";

echo "Checking: Existing machines in scan profile to update.\n\n";

// And now update the machine list to use the new format
$stmt02->execute();

while($row = $stmt02->fetch_assoc()) {
	$row_id = $row['row_id'];
	$machine = ":gen:" . $row['machine'];

	$stmt03->execute($machine, $row_id);
}

echo "\tChanged " . $stmt02->num_rows() . " machines to use new format\n\n";
echo "Updating scan profiles to use default scanner\n\n";

// And now add the new scanner ID as the default for all saved profiles
$stmt04->execute();

while($row = $stmt04->fetch_assoc()) {
	$profile_id = $row['profile_id'];
	$stmt05->execute($scanner_id, $profile_id);
}

echo "\tUpdated " . $stmt04->num_rows() . " profiles to use default scanner\n\n";

/**
* Update the help tables, add new help topics
* Insert all the help topics
*/
echo "Updating help tables and adding new content\n\n";
$db->load_sql_file(_ABSPATH."/upgrade/2.0_to_2.5/sql/help.sql");
$db->load_sql_file(_ABSPATH."/upgrade/2.0_to_2.5/sql/help-categories.sql");

unlink(_ABSPATH."/upgrade/2.0_to_2.5/sql/upgrade.sql");
unlink(_ABSPATH."/upgrade/2.0_to_2.5/sql/help.sql");
unlink(_ABSPATH."/upgrade/2.0_to_2.5/sql/help-categories.sql");

echo "done\n";

?>
