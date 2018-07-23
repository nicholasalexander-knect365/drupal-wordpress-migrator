<?php

// backup the menu
require "../DB.class.php";
require "../WP.class.php";
require "../WPTerms.class.php";
require "../WPTermMeta.class.php";

require "../Initialise.class.php";
require "../Options.class.php";
//require "Post.class.php";
//require "WPTermMeta.class.php";
require "../User.class.php";
require "../Node.class.php";
require "../Post.class.php";
require "../Taxonomy.class.php";

// common routines including script init
require "../common.php";

/* nodes */
$d7_node = new Node($d7);
$wp_post = new Post($wp, $options);

// databases are now available as $wp and $d7
// $wordpress = new WP($wp, $options);

$nodes = $d7_node->getAllNodes();
$wp_terms = new WPTerms($wp, $options);
$wp_termmeta = new WPTermMeta($wp);
$wp_termmeta_term_id = $wp_termmeta->getTermMetaValue(DRUPAL_WP, DRUPAL_WP);

foreach($nodes as $node) {

	$nid = $node->nid;
	$type = $node->type;
	if ($type === 'media_entity' || $type === 'block_content' || $type === 'display_admin') {
		continue;
	}

	$wp_post_id =$wp_post->nodeToPost($nid);

//debug($nid . ' :: ' .$type . '>>>>>>>>>>>>>' . $wp_post_id);

	$sql = "SELECT field_penton_summary_value AS excerpt 
			FROM field_data_field_penton_summary 
			WHERE entity_id = $nid";
	$records = $d7->records($sql);

//debug(DB::strip($sql));

//debug($records);

	if (isset($records) && count($records) && $wp_post_id) {
		if (count($records) === 1) {

//debug(DB::strip($sql));

			$excerpt = $records[0]->excerpt;
			if ($excerpt !== '<html><head><title></title></head><body></body></html>') {
				// what is wordpress post id for this nid
				if ($wp_post_id !== 536) {
					$sql = $wp_post->updatePost($wp_post_id, 'post_excerpt', $excerpt, true);
					print "\n".$sql.";\n";
				}
			}
			//debug($records);
		} else {
			throw new Exception("\n\nFound more than one post with ".DB::strip($sql));
//			dd($records);
		}
	}
}
