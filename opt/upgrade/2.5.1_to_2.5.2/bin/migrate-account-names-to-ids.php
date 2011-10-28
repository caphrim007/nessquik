<?php

/**
* This script will migrate the use of usernames
* in several tables to account_ids and will update
* the accounts table with the new IDs
*/

if (!@$argc) {
	die ("<p>This script can only be run from command line");
}

define("_ABSPATH", dirname(dirname(dirname(dirname(__FILE__)))));

require_once(_ABSPATH.'/confs/config-inc.php');
require_once(_ABSPATH.'/db/nessquikDB.php');

$db = nessquikDB::getInstance();

$sql = array(
	'exists' => "SELECT id FROM accounts WHERE username=':1'",
	'distinct-accounts' => "SELECT DISTINCT(username) FROM profile_list;",
	'insert-account' => "INSERT INTO accounts(`username`) VALUES (':1');",
	'profile-list' => "UPDATE profile_list SET account_id=':1' WHERE username=':2';",
	'profile-settings' => "UPDATE profile_settings SET account_id=':1' WHERE username=':2';",
	'ss-results' => "UPDATE saved_scan_results SET account_id=':1' WHERE username=':2';",
	'whitelist' => "UPDATE whitelist SET account_id=':1' WHERE username=':2';"
);

$stmt1 = $db->prepare($sql['distinct-accounts']);
$stmt2 = $db->prepare($sql['insert-account']);

$stmt3 = $db->prepare($sql['profile-list']);
$stmt4 = $db->prepare($sql['profile-settings']);
$stmt5 = $db->prepare($sql['ss-results']);
$stmt6 = $db->prepare($sql['whitelist']);

$stmt7 = $db->prepare($sql['exists']);

$stmt1->execute();

while($row = $stmt1->fetch_assoc()) {
	$username = $row['username'];

	$stmt7->execute($username);

	if ($stmt7->num_rows() > 0) {
		continue;
	}

	$stmt2->execute($username);

	$account_id = $stmt2->last_id();

	$stmt3->execute($account_id,$username);
	$stmt4->execute($account_id,$username);
	$stmt5->execute($account_id,$username);
	$stmt6->execute($account_id,$username);
}

?>
