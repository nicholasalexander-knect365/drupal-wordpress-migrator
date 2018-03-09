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
$wp_taxonomy->createTerms($taxonomies);
	

die();	

	
foreach ($drupal_nodes as $node) {

	// // find the taxonomies for this node
	// $d7->query("SELECT nid, tid FROM taxonomy_index WHERE nid=" . $node->nid);
	// $tids = $d7->getRecords();

	$tids = $d7_taxonomy->taxonomyListForNode($node);

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
print "\n";
print $node->title . ' ::: ';

print implode(', ',$taxonomyNames);
print "\n";
print implode(',', $taxonmyIds);
die();	

	// find the WP post that related to this node
	$sql = "SELECT post_id FROM wp_postmeta WHERE meta_value='".$node->nid."' AND meta_key='_fgd2wp_old_node_id'";

	$wp->query($sql);
	$posts = $wp->getRecords();

die($sql);



}
$wp->close();
$d7->close();
