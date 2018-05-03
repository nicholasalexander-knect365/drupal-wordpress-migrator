<?php

/***
 * imageProcess.php
 * migrator.php script
 * by Nicholas Alexander for Informa Knect365
 * 
 * purpose: to check for drupal images that have not been migrated
 * 
 */
require "DB.class.php";
require "WP.class.php";

require "Initialise.class.php";
require "Options.class.php";
require "Post.class.php";
require "WPTermMeta.class.php";
require "User.class.php";
require "Node.class.php";
require "Files.class.php";

// common routines including script init
require "common.php";
// databases are now available as $wp and $d7

$wordpress = new WP($wp, $options);

/* nodes */
$d7_node = new Node($d7);
$wp_post = new Post($wp);