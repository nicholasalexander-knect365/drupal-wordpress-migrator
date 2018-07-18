<?php
/***
 * user validity check
 */
require "../DB.class.php";
require "../WP.class.php";

require "../Initialise.class.php";
require "../Options.class.php";
require "../User.class.php";
require "../Node.class.php";

// common routines include script initialisation
require "../common.php";

$credentials = ['host' => 'mysql', 'username' => 'test2_tele_com', 'password' => 'OhLoogai1Jook5mai7oc', 'database' =>'test2_telecoms_com'];

$db2 = new mysqli(
			$credentials['host'],
			$credentials['username'],
			$credentials['password'],
			$credentials['database']);

$credentials = ['host' => 'mysql', 'username' => 'test3_tele_com', 'password' => '6ORjLxbk9I8hs7tGwMFE', 'database' => 'test3_telecoms_com'];
$db3 = new mysqli(
			$credentials['host'],
			$credentials['username'],
			$credentials['password'],
			$credentials['database']);

$sql = "SELECT COUNT(*) as c FROM test2_telecoms_com.wp_users";
$db2->query($sql);
$record2 = $db2->getRecord();


$sql = "SELECT COUNT(*) as c FROM test3_telecoms_com.wp_users";
$db3->query($sql);
$record3 = $db3->getRecord();

print "\nRecord counts : test2=".$record2->c;

print "\nRecord counts : test3=".$record3->c;

