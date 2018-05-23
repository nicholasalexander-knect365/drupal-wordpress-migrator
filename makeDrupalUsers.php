<?php
/***
 * php makeDrupalUsers: run on any server 
 * to create an SQL table of drupal users 
 * for import on a LIVE server using createWPusers.php
 */
require "DB.class.php";
require "WP.class.php";

require "Initialise.class.php";
require "Options.class.php";
require "User.class.php";

// common routines include script initialisation
require "common.php";

// databases are now available as $wp and $d7
$server = 'local';

$users->getDrupalUsers(); //debug($users->drupalUsersLoaded() . ' users loaded from Drupal');

$users->makeDrupalUsers();

debug("\n\nDrupal users have been stored in the wordpress dusers table\n\n");
