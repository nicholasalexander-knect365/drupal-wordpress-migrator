<?php

/***
 * imageProcess.php
 * migrator.php script
 * by Nicholas Alexander for Informa Knect365
 * 
 * purpose: to check for drupal images that have not been migrated
 * notes: migrator takes a node first approach: finding images for each node
 *        this script takes an image centric approach 
 *      - for each image, look for how it is used, 
 *        and if it is not, still add it to the media library
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

$sql = "SELECT fu.fid, fu.module, fu.type, fu.id, fu.count, fm.uid, fm.filename as filename, fm.uri as uri, fm.filesize, fm.status, fm.timestamp   FROM file_managed fm  JOIN file_usage fu ON fm.fid=fu.fid where fu.type = 'node'";