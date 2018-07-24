<?php

// fix the primary category

// backup the menu
require "../DB.class.php";
require "../WP.class.php";
//require "../WPTerms.class.php";
//require "../WPTermMeta.class.php";

//require "../Initialise.class.php";
require "../Options.class.php";
//require "Post.class.php";
//require "WPTermMeta.class.php";
//require "../User.class.php";
require "../Node.class.php";
require "../Post.class.php";
require "../PostMeta.class.php";
require "../Taxonomy.class.php";

// common routines including script init
require "../common.php";

/* nodes */
$d7_node = new Node($d7);
$wp_post = new Post($wp, $options);
$postmeta = new PostMeta($wp, DB::wptable('postmeta'));
// databases are now available as $wp and $d7
// $wordpress = new WP($wp, $options);

$nodes = $d7_node->getAllNodes();
$wp_taxonomy = new Taxonomy($wp, $options);
$d7_taxonomy = new Taxonomy($d7, $options);
// $wp_terms = new WPTerms($wp, $options);
// $wp_termmeta = new WPTermMeta($wp);
// $wp_termmeta_term_id = $wp_termmeta->getTermMetaValue(DRUPAL_WP, DRUPAL_WP);

foreach($nodes as $node) {

	$nid = $node->nid;
	$type = $node->type;
	if ($type === 'media_entity' || $type === 'block_content' || $type === 'display_admin') {
		continue;
	}

	$wpPostId =$wp_post->nodeToPost($nid);

//debug($nid . ' :: ' .$type . '>>>>>>>>>>>>>' . $wp_post_id);

	$sql = "SELECT field_penton_primary_category_tid AS primary_tid 
			FROM field_data_field_penton_primary_category 
			WHERE entity_type='node' AND entity_id = $nid";
	$records = $d7->records($sql);

//debug(DB::strip($sql));

//debug($records);
//debug($records);
	if (isset($records) && count($records) && $wpPostId) {
		if (count($records) === 1) {

//debug(DB::strip($sql));

			$tid = $records[0]->primary_tid;
			// how to work out the primary category on tid?
			$catName = $d7_taxonomy->getTaxonomyDrupal($tid);
			//$remapTaxonomy = new RemapTaxonomy($wp_taxonomy, $options);
			$wpCatName = $wp_taxonomy->remapIOTTaxonomyName($catName);
			// find the category in terms
			$wpCatId = $wp_taxonomy->getTermFromName($wpCatName);

			if (strlen($wpCatName)) {
debug($wpCatName .' '.  $wpCatId .' '. $tid);
				$postmeta->createGetPostMeta($wpPostId, 'primary_category', $wpCatId);
			}
		} else {
			throw new Exception("\n\nFound more than one post with ".DB::strip($sql));
//			dd($records);
		}
	}
}
