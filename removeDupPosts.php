<?php
/***
 * replacecContent.php
 * migrator.php script 
 * by Nicholas Alexander for informa Knect365
 * 
 * purpose: to migrate drupal nodes into wp-posts 
 * with no side effects
 * 
 * use: 
 * php replaceContent.php 
 * --server=[staging,vm,local] --wordpressPath=/path/to/wordpress --project=[tuauto.iotworldtoday] --clean (strips out styles from html tags)
 */
require "DB.class.php";
require "WP.class.php";
print "\nRemove duplicate posts, keeping the lastest one.\n";

require "Initialise.class.php";
require "Options.class.php";
require "Post.class.php";
require "WPTermMeta.class.php";
require "User.class.php";
require "Node.class.php";
require "Taxonomy.class.php";

// common routines including script init
require "common.php";

// databases are now available as $wp and $d7
$wordpress = new WP($wp, $options);

/* nodes */
$d7_node = new Node($d7);
$wp_post = new Post($wp, $options);

$wp_termmeta = new WPTermMeta($wp);

$wp_taxonomy = new Taxonomy($wp, $options);
//$drupal_wp_term_id = $wp_taxonomy->getSetTerm(DRUPAL_WP, DRUPAL_WP);

// if (!$drupal_wp_term_id) {
// 	throw new Exception("ERROR: \nNo Drupal Node ID record in wp_postmeta\n");
// }

$wp_posts = DB::wptable('posts');

$sql = "SELECT ID, post_status, post_title, post_name, post_author from $wp_posts p WHERE EXISTS (SELECT 1 from $wp_posts p2 WHERE p2.post_name = p.post_name LIMIT 1,1) order by post_name, ID";

//(DB::strip($sql));

$c = 0;
$records = $wp->records($sql);

$buffer = [];
$current_name = '';
$verbose = false;

foreach($records as $record) {

	if (strlen($current_name) === 0 || ($current_name === $record->post_name)) {
		$buffer[] = $record;
		if ($verbose) {
			debug("\nBuffered:");
			debug($record);
		}
	} else {
		$editions = count($buffer);
		$cc = 0;
		
		if ($editions > 1) {
			if ($verbose) print "\n\n\n ".$editions.' editions';
			foreach($buffer as $n => $post) {
				$post_id = $post->ID;
				$cc++;
				if ($cc < $editions) {
					if ($verbose) print "\n$n $cc Revision: " . $post->ID . ' ' . $post->post_author . ' ' . $post->post_status;
					//$sql = "UPDATE $wp_posts SET post_status = 'revision' WHERE ID=$post_id";
					$sql = "DELETE FROM $wp_posts where ID=$post_id";
					if ($verbose) print "\n".$sql;
					$wp->query($sql);

				} else {
					if ($verbose) print "\n$n $cc LATEST: " . $post->ID . ' ' . $post->post_author . ' ' . $post->post_status;

				}
			}
			$buffer = [];
		}
		$buffer[] = $record;
	}

	$current_name = $record->post_name;
}



print "\nCompleted\n\n";

$wp->close();
$d7->close();
