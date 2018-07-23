<?php

// backup the menu - WIP

die('menuBackup script is not finished.  Script stopped.');


require "../DB.class.php";
require "../WP.class.php";

require "../Initialise.class.php";
require "../Options.class.php";
//require "Post.class.php";
//require "WPTermMeta.class.php";
require "../User.class.php";
//require "Node.class.php";
require "../Taxonomy.class.php";

// common routines including script init
require "../common.php";

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

$sql = "SELECT *  FROM $wp_terms AS t 
		LEFT JOIN wp_term_taxonomy AS tt ON tt.term_id = t.term_id 
		WHERE tt.taxonomy = 'nav_menu'";
$menu_terms = $wp->records($sql);

dd(DB::strip($sql));

$sql = "SELECT * FROM wp_posts WHERE post_type = 'nav_menu_item'";
$menu_items = $wp->records($sql);

dd($menu_terms);

$menu_entries = [];
foreach($menu_terms as $term) {
	$term_id = $term->id;
	$sql = "SELECT p.* FROM wp_posts AS p 
		LEFT JOIN wp_term_relationships AS tr ON tr.object_id = p.ID
		LEFT JOIN wp_term_taxonomy AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
		WHERE p.post_type = 'nav_menu_item'
		 AND tt.term_id = $term_id";
	  $menu_entries[] = $wp_records($sql);
}

dd($menu_entries);