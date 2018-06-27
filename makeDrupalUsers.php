<?php
/***
 * php makeDrupalUsers: run on any server 
 * to create an SQL table of drupal users (dusers) under Wordpress
 * for import on a LIVE server using createWPusers.php
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
$users->getDrupalUsers(); 
debug($users->drupalUsersLoaded() . ' users loaded from Drupal');
$users->makeDrupalUsers();

debug("\n\nDrupal users have been stored in the wordpress dusers table\n\n");
