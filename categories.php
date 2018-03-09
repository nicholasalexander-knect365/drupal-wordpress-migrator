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

$taxonomies = $d7_taxonomy->taxonomyList();

//var_dump($taxonomies);die;

// create the wp_terms for each of the taxonomies
//$wp_taxonomy->cleanUp();
$wp_taxonomy->createTerms($taxonomies);


foreach ($drupal_nodes as $node) {

	$tids = $d7_taxonomy->taxonomyListForNode($node);

var_dump($tids);

	foreach ($tids as $tid) {
		// $d7->query("SELECT * FROM taxonomy_term_data where tid=" . $tid->tid);
		// $taxonomies = $d7->getRecords();
		$termData = $d7_taxonomy->termData($tid->tid);
		// debugging...		
		foreach ($termData as $term) {
			$taxonomyNames[$term->tid] = $term->name;
			$taxonmyIds[] = $term->tid;
		}
	}

	// now create the wp-
// print "\n";
// print $node->title . ' ::: ';

// print implode(', ',$taxonomyNames);
// print "\n";
// print implode(',', $taxonmyIds);
// die();	

	// find the WP post that related to this node
	$sql = "SELECT post_id FROM wp_postmeta WHERE meta_value='".$node->nid."' AND meta_key='_fgd2wp_old_node_id'";

	$wp->query($sql);
	$posts = $wp->getRecords();

// figure out how to get the wordpress ids to replace the tids


	// create noees in wp_term_relationships to associate to $termData ids
	foreach ($posts as $post) {
var_dump('post',$post);
		foreach ($termData as $term) {
var_dump('d7 term',$term);

		$sql = "INSERT INTO wp_term_relationships (object_id, term_taxonomy_id, term_order) VALUES (" . $post->post_id . ', ' . $term->tid . ", 0)";

print "\n" . $sql;
		}
	}
	die();
}
$wp->close();
$d7->close();
