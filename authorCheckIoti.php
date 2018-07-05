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
 
 BUG: THIS SCRIPT selects wrong DB in common configs??  i.e. it tries to run Wordpress queries on Drupal - needs more config

 */

die('authorCheckIoit.php - script does not work correctly, use migrator')

require "DB.class.php";
require "WP.class.php";

require "Initialise.class.php";
require "Options.class.php";
require "Post.class.php";
require "WPTermMeta.class.php";
require "User.class.php";

require "Node.class.php";
require "Files.class.php";
require "Taxonomy.class.php";

require "Fields.class.php";
require "FieldSet.class.php";
require "Gather.class.php";

// common routines including script init
require "common.php";

$users = new User($wp, $d7, $options);

print "\nReplace Content: this script replaces post content and populates users (with -u option)\n";
print "\n\nThere are ".$users->wordpressUsers();

// databases are now available as $wp and $d7
$wordpress = new WP($wp, $options);

/* nodes */
$d7_node = new Node($d7);
$wp_post = new Post($wp, $options);

$wp_termmeta = new WPTermMeta($wp);

$wp_taxonomy = new Taxonomy($wp, $options);
$drupal_wp_term_id = $wp_taxonomy->getSetTerm(DRUPAL_WP, DRUPAL_WP);

if (!$drupal_wp_term_id) {
	throw new Exception("ERROR: \nNo DRUPAL_WP record in wp_postmeta\n");
}

$includeUsers = isset($options->users);

$sql = "SELECT u.name, a.field_penton_author_target_id as author_id, n.* 
		FROM `field_data_field_penton_author` a 
		JOIN node n ON a.entity_id = n.nid 
		LEFT JOIN users u ON u.uid=a.field_penton_author_target_id";

$records = $d7->records($sql);
$c=0;
$m=0;
$a=0;
$verbose = true;

foreach($records as $record) {
	$nid = $record->nid;
	// does this record exist, does it have the right author?
	$node = $d7_node->getNode($record->nid, $record->vid);
	$post_id = $wp_post->nodeToPost($nid);
	if (!$post_id) {
		continue;
	}
	$post = $wp_post->getPost($post_id);
	$c++;
	if (!$post) {
		$m++;
		debug('Post Missing for NID ' . $nid);
	} else {
		if ($post->post_author !== $record->author_id) {
			$a++;
			if ($verbose) debug($post->ID . ' ' . $post->post_author . ' ' . $record->author_id . ' '. $post->post_title);

			// find the usermeta that returns the wordpress author
			//$wpUserId = $wordpress->getWordpressUserId($record->author_id, $drupalUid);
$sql = "SELECT * FROM wp_usermeta WHERE meta_key='$drupal_uid' AND meta_value LIKE '$uid'";
$record = $wp->query($sql);
$wpUserId = $record->user_id;

			if ($wpUserId) {
				debug('UPDATE: post author being set to '.$wpUserId);
			} else {
				debug('WP User info not found for D:'.$record->author_id. ' W:'.$wpUserId);
			}
			//$wp_post->updatePostRecord($post, 'post_author', $wpUserId);
		}
	}
}
print "\nNodes processed: $c  missing post: $m  author not assigned: $a";

print "\nCompleted\n\n";

$wp->close();
$d7->close();
