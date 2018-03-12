<?php

require "DB.class.php";
require "Taxonomy.class.php";


$wp = new DB('wp');
$d7 = new DB('d7');

$wp_taxonomy = new Taxonomy($wp);
$d7_taxonomy = new Taxonomy($d7);

// build the term_taxonomy if not already present
if ($wp_taxonomy->checkTerms()) {
	$wp_taxonomy->buildTerms();
}

$d7->query('SELECT * FROM `node`');

$drupal_nodes = $d7->getRecords();

$taxonomyNames = [];

$taxonomies = $d7_taxonomy->fullTaxonomyList();

$wp_taxonomy->createTerms($taxonomies);

foreach ($drupal_nodes as $node) {

	$termData = $d7_taxonomy->taxonomyListForNode($node);
	$nid = $node->nid;

	$sql = "SELECT pm.post_id, pm.meta_value
			FROM wp_postmeta pm
			WHERE pm.meta_value=$nid AND meta_key='_fgd2wp_old_node_id'";

	$wp->query($sql);
	$posts = $wp->getRecords();

	foreach ($posts as $post) {

		$post_id = $post->post_id;
		#print "\nProcessing ".$nid . " termdata ".count($termData) . ' WPpostId ' . $post_id ;

		foreach ($termData as $term) {
			$tid = $term->tid;
			// find the wp_term
			$sql = "SELECT tx.term_taxonomy_id FROM wp_terms 
					LEFT JOIN wp_term_taxonomy tx ON tx.term_id=wp_terms.term_id 
					WHERE term_group=$tid ";

			$wp->query($sql);
			$wp_term = $wp->getRecord();
		
			if ($wp_term) {
				$term_taxonomy_id = $wp_term->term_taxonomy_id;

				$sql = "REPLACE INTO wp_term_relationships 
					(object_id, term_taxonomy_id, term_order) 
					VALUES ($post_id, $term_taxonomy_id, 0)";

				$wp->query($sql);

				// add to the count in wp_term_taxonomy
				$sql = "UPDATE wp_term_taxonomy SET count=1 WHERE term_taxonomy_id=$term_taxonomy_id LIMIT 1";
				$wp->query($sql);			
			}
		}
	}
}

// clear out the term groups from wp_terms
$sql = "UPDATE wp_terms SET term_group=0";
$wp->query($sql);

$wp->close();
$d7->close();
