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
print "\nReplace Content: this script replaces post content and populates users (with -u option)\n";

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
$drupal_wp_term_id = $wp_taxonomy->getSetTerm(DRUPAL_WP, DRUPAL_WP);

if (!$drupal_wp_term_id) {
	throw new Exception("ERROR: \nNo DRUPAL_WP record in wp_postmeta\n");
}

$includeUsers = isset($options->users);

// process nodes -> wp posts ONLY //

// how many nodes to process?
$nodeCount = $d7_node->nodeCount();

if ($nodeCount > $maxChunk) {
	$chunk = floor($nodeCount / $maxChunk);
} else {
	$chunk = $nodeCount;
}

$d7_node->setNodeChunkSize($nodeCount);
$chunks = floor($nodeCount / $chunk);

// set a value ONLY for a test version that only does a few posts
$TESTLIMIT = null;

for ($c = 0; $c < $chunks; $c++) {

	$drupal_nodes = $d7_node->getNodeChunk($TESTLIMIT);
	print "\n\nReplacing content using " . count($drupal_nodes). " Drupal Nodes to update Wordpress posts and usermeta (with -u)\n";

	if (isset($drupal_nodes) && count($drupal_nodes)) {

		foreach ($drupal_nodes as $node) {

			$wpPostId = $wp_termmeta->getTermMetaValue($drupal_wp_term_id, $node->nid);
//print "\n$wpPostId, $drupal_wp_term_id, $node->nid";
			if ($wpPostId) {
				$wp_post->replacePostContent($wpPostId, $node, $includeUsers, $users);
			} else {
				throw new Exception("\n\nNo Drupal <--> WP flag record in wp_termmeta.\n");
			}

			if ($node->nid % 10 === 0) {
				print '.';
			}
		}
	}
}

print "\nCompleted\n\n";

$wp->close();
$d7->close();
