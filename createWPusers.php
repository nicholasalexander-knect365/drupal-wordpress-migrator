<?php
/***
 * createWPusers.php
 * one off script to run on LIVE server to build the Wordpress 
 * Users from the dusers temporary table (created by makeDruapalUsers.php)
 * duplicates and REPLACES php migrator.php -u 
 * RUN ON A LIVE SERVER: creates Wordpress users 
 */
require "DB.class.php";
require "WP.class.php";

require "Initialise.class.php";
require "Options.class.php";
require "User.class.php";

// common routines include script initialisation
require "common.php";

// databases are now available as $wp and $d7
$wordpress = new WP($wp, $options);

// if the -u option is given: process users
if ($options->users) {

	// if dusers flag is set, read the users from the dusers temporary table
	if ($options->dusers) {

		if ($users->getTempDrupalUsers()) {
			debug($users->countDrupalUsers() . ' users loaded from temporary table (dusers)');
		} else {
			debug ("\nError: Did not load users from the temporary dusers table, dusers requires a table to load from.");
		}

	} else {

		if ($users->doWordpressUsersExist()) {

			debug("\nImporting Drupal users to add to existing Wordpress users");
		} 
		$users->getDrupalUsers(); 
	}

	debug($users->drupalUsersLoaded() . ' users loaded from Drupal');

	$users->createWordpressUsers($options->siteId);  
	debug($users->wordpressUsers() . '... users created in Wordpress');

	$users->makeAdminUser();

	die("\n\nUsers imported, now run without the -u switch to do imports using these users.\n\n");

} else {

	$wpUsers = $users->countWordpressUsers();
	if (!$wpUsers) {
		die("\nERROR: wordpress users do not yet exist - you need to run with a -u flag\n");
	} else {
		debug("\nWordpress already has $wpUsers users");
	}
}

die("\n\nEnd of script\n\n");