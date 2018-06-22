<?php
/***
 * rewriteSlugs.php
 * purpose: reset wpterms slugs to be slugs of the name
 * ONE OFF FOR TUAUTO for a bug-fix
 * NO LONGER REQUIRED
 * by Nicholas Alexander for informa Knect365
 * 
 */
require "DB.class.php";
require "WP.class.php";

require "Initialise.class.php";
require "Options.class.php";
//require "Post.class.php";
//require "WPTermMeta.class.php";
require "User.class.php";
//require "Node.class.php";
require "Taxonomy.class.php";

die('rewriteSlugs is a one-off script to fix a migration bug: no longer required.  It will be deprecated');

// common routines including script init
require "common.php";

// databases are now available as $wp and $d7
$wordpress = new WP($wp, $options);

/* nodes */
//$d7_node = new Node($d7);
//$wp_post = new Post($wp, $options);

//$wp_termmeta = new WPTermMeta($wp);

$wp_taxonomy = new Taxonomy($wp, $options);
//$drupal_wp_term_id = $wp_taxonomy->getSetTerm(DRUPAL_WP, DRUPAL_WP);

// if (!$drupal_wp_term_id) {
// 	throw new Exception("ERROR: \nNo Drupal Node ID record in wp_postmeta\n");
// }
$wp_terms = DB::wptable('terms');

$terms = "SELECT * FROM $wp_terms";
$records = $wp->records($terms);
foreach ($records as $term) {
	$id = $term->term_id;
	$slug = Taxonomy::slugify($term->name);
	$sql = "UPDATE $wp_terms SET slug='$slug' WHERE term_id=$id LIMIT 1";
	//debug($sql);
	$wp->query($sql);
}


