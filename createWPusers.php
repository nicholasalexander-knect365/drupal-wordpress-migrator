<?php
/***
 * php migrator.php Version 1.08
 *
 * by Nicholas Alexander for Informa Knect365
 *
 *purpose: migrate a drupal instance into a wordpress instance
 *
 * options -d default mode 
 * location settings:
 * --wordpressPath= --drupalPath= --wordpressURL= --imageStore=
 * conversions included settings:
 * -f files (images)
 * -c ACF fields
 * -t taxonomy
 * -n nodes
 * -d default modes (with --server)
 */
require "DB.class.php";
require "WP.class.php";

require "Initialise.class.php";
require "Options.class.php";
require "Post.class.php";
require "PostMeta.class.php";
require "WPTermMeta.class.php";
require "User.class.php";

require "Node.class.php";
require "Files.class.php";
require "Taxonomy.class.php";

require "Fields.class.php";
require "FieldSet.class.php";
require "Gather.class.php";

// common routines include script initialisation
require "common.php";

// databases are now available as $wp and $d7
$wordpress = new WP($wp, $options);

/* nodes */
$d7_node = new Node($d7);
$wp_post = new Post($wp, $options);

// use termmeta to record nodeIds converted to wordpress IDs
$wp_termmeta = new WPTermMeta($wp);

if ($options->users) {
	// do not clear users unless it is specified
	// read and transfer all users if -u specified

	if ($users->doWordpressUsersExist()) {
		debug('Importing Drupal users to existing Wordpress users');
	} 

	// if dusers flag is set, read the users from the dusers temporary table
	if ($options->dusers) {

		$users->getTempDrupalUsers();
		debug($users->countDrupalUsers() . ' users from temporary table (dusers)');

	} else {
		$users->getDrupalUsers(); //debug($users->drupalUsersLoaded() . ' users loaded from Drupal');
	}
	debug("\nDrupal users loaded: " . $users->countDrupalUsers() . "\n\n");

	$users->createWordpressUsers($options->siteId);  //debug($users->wordpressUsers() . '... users created in Wordpress');

	$users->makeAdminUser();

	die("\n\nUsers imported, now run without the -u switch to do imports using these users.\n\n");

} else {
	if (!$users->doWordpressUsersExist()) {
		die("\nERROR: wordpress users do not yet exist - you need to run with a -u flag\n");
	}
}

die("\n\nEnd of script\n\n");