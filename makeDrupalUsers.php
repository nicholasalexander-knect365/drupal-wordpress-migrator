<?php
/***
 * php makeDrupalUsers
 */
require "DB.class.php";
require "WP.class.php";

require "Initialise.class.php";
require "Options.class.php";
require "User.class.php";

// common routines include script initialisation
require "common.php";

// databases are now available as $wp and $d7
//$wordpress = new WP($wp, $options);

// $options = [];
$server = 'local';

// /* nodes */
// $d7_node = new Node($d7);
// $wp_post = new Post($wp, $options);

// // use termmeta to record nodeIds converted to wordpress IDs
// $wp_termmeta = new WPTermMeta($wp);

// do not clear users unless it is specified
// read and transfer all users if -u specified

//$users = new User($wp, $d7, $options);
	// if ($users->doWordpressUsersExist()) {
	// 	debug('Importing Drupal users to existing Wordpress users');
	// }
	$users->getDrupalUsers(); //debug($users->drupalUsersLoaded() . ' users loaded from Drupal');
// 		$sql = "SELECT u.uid, u.name, u.mail, u.signature, u.timezone, u.language, u.created, r.name as role
// 			FROM users u
// 			LEFT JOIN users_roles ur ON ur.uid = u.uid
// 			LEFT JOIN role r on r.rid = ur.rid";
		
// 		var_dump($d7->connection);die;

// 		$d7->connection->query($sql);
// 		$rows = [];
// 		while ( $row = $d7->connection->fetch_object()) {
// 			$rows[] = $row;
// 		}
// dd($rows);
// 		$this->drupalUsers = $d7->connection->getRecords();

		// if (count($this->drupalUsers) > MAX_USERS) {
		// 	debug('Warning: there are more than ' . MAX_USERS);
		// }
	
	$users->makeDrupalUsers();
