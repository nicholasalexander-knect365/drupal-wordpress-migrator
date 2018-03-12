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

//var_dump($taxonomies);die;

// create the wp_terms for each of the taxonomies
//$wp_taxonomy->cleanUp();
$wp_taxonomy->createTerms($taxonomies);


foreach ($drupal_nodes as $node) {

	$termData = $d7_taxonomy->taxonomyListForNode($node);
	$nid = $node->nid;
// var_dump($tids);
// die;
	// make a list of taxonomies for this node
	// foreach ($tids as $tid) {
	// 	// $d7->query("SELECT * FROM taxonomy_term_data where tid=" . $tid->tid);
	// 	// $taxonomies = $d7->getRecords();
	// 	$termData = $d7_taxonomy->termData($tid->tid);
	// 	// debugging...		
	// 	foreach ($termData as $term) {
	// 		$taxonmyIds[] = $term->tid;
	// 		$taxonomyNames[$term->tid] = $term->name;
	// 	}
	// }

	// now create the wp-
// print "\n";
// print $node->title . ' ::: ';

// print implode(', ',$taxonomyNames);
// print "\n";
// print implode(',', $taxonmyIds);
// die();	

	// find the WP post that related to this node
	$sql = "SELECT pm.post_id, pm.meta_value
			FROM wp_postmeta pm
			WHERE pm.meta_value=$nid AND meta_key='_fgd2wp_old_node_id'";

	$wp->query($sql);
	$posts = $wp->getRecords();
// print $sql;
// var_dump($nid, $posts);die;
// figure out how to get the wordpress ids to replace the tids


	// create (object_id, term_taxonomy_id) in wp_term_relationships 
	// to associate to $termData ids
	// these relations come from ther term_taxonomy 
	// (term_taxonomy_id, term_id)
	
	foreach ($posts as $post) {
		$post_id = $post->post_id;
print "\nProcessing ".$nid . " termdata ".count($termData) . ' WPpostId ' . $post_id ;
//var_dump('post',$post);
		foreach ($termData as $term) {
			$tid = $term->tid;
			// find the wp_term
			$sql = "SELECT tx.term_taxonomy_id FROM wp_terms 
			LEFT JOIN wp_term_taxonomy tx on tx.term_id=wp_terms.term_id WHERE term_group=$tid ";

			$wp->query($sql);
			$wp_term = $wp->getRecord();
			if ($wp_term) {
				$term_taxonomy_id = $wp_term->term_taxonomy_id;
//print ' ' . $term_id;

				$sql = "REPLACE INTO wp_term_relationships 
					(object_id, term_taxonomy_id, term_order) 
					VALUES ($post_id, $term_taxonomy_id, 0)";

				$wp->query($sql);
			}
//print "\n" . $sql;
		}
	}
}

// clear out the term groups from wp_terms
$sql = "UPDATE wp_terms SET term_group=0";
$wp->query($sql);

$wp->close();
$d7->close();
