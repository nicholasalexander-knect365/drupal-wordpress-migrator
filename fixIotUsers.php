<?php
/***
 * fixIotiUsers.php
 * one off script to run on UAT server to assign drupal users to wp_38_capabilities 
 * (they were assigned to wp_capabilites in wp_usermeta)
 */
require "DB.class.php";
require "WP.class.php";

require "Initialise.class.php";
require "Options.class.php";
require "User.class.php";

// common routines include script initialisation
require "common.php";

$users = new User($wp, $d7, $options);

// databases are now available as $wp and $d7
$wordpress = new WP($wp, $options);

$drupal_nodes = $d7_node->getAllNodes();

foreach($drupal_nodes as $node) {
	$uid = $node->uid;
	$duser = $users->getDrupalUser($uid);

	// does user exist?
	// no: report missing user
	// yes: add a wp_38_capabilities for this user

}

die("\n\nEnd of script\n\n");
