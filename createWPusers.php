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

$chunk = 250;
$gap = 3;
$counter = 0;

$BT = "START TRANSACTION";
$CT = "COMMIT";

$users = new User($wp, $d7, $options);

// databases are now available as $wp and $d7
$wordpress = new WP($wp, $options);

if ($chunk > 0) {
	$dusers_size = $users->countDUsers();
}

// if the -u option is given: process users
if ($options->users) {

	// if dusers flag is set, read the users from the dusers temporary table
	if ($options->dusers) {
		if ($chunk) {
			for ($counter = 0; $counter <= ($dusers_size / $chunk); $counter++) {
				//debug("\nChunk $counter From ".$counter*$chunk." for $chunk ".$dusers_size . ' '. floor($dusers_size/$chunk));
				try {
					$wp->query($BT);
					debug("\nChunk $counter From ".$counter*$chunk." for $chunk ");
					$usersCreated = $users->getTempDrupalUsers($chunk);
					print(" ... creating ".$usersCreated.' users in Wordpress ');
					$users->createWordpressUsers($options->siteId);
					print(" ... ZZzzz ... ");
					$wp->query($CT);
				} catch (Exception $e) {
					throw new Exception("MYSQL ERROR: ".$e->getMessage());
				}
				sleep($gap);
			}
		} else {
			if ($users->getTempDrupalUsers()) {
				debug($users->countDrupalUsers() . ' users loaded from temporary table (dusers)');
			} else {
				debug ("\nError: Did not load users from the temporary dusers table, dusers requires a table to load from.");
			}
		}
	} else {
		if ($users->doWordpressUsersExist()) {
			debug("\nImporting Drupal users to add to existing Wordpress users");
		} 
		$users->getDrupalUsers(); 
	}

	if (!$chunk) {
		debug($users->drupalUsersLoaded() . ' users loaded from Drupal');
		$users->createWordpressUsers($options->siteId); debug($users->wordpressUsers() . '... users in Wordpress');
	}
	$users->makeAdminUser();


	die("\n\nUsers imported, now run without the -u switch to do imports using these users.\n\n");

} else {

	$wpUsers = $users->countWordpressUsers();
	if (!$wpUsers) {
		die("\nERROR: wordpress users do not yet exist - you need to run with a -u flag\n");
	} else {
		debug("\nWordpress already has $wpUsers users.  To confirm you want to add the " . $users->countDrupalUsers() . ' Drupal users from ' . $options->project . ' please use a -u flag.');
	}
}

die("\n\nEnd of script\n\n");