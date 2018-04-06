<?php

require "DB.class.php";
require "Options.class.php";
require "Post.class.php";

require "Node.class.php";
require "Files.class.php";
require "Taxonomy.class.php";

require "Fields.class.php";
require "FieldSet.class.php";
require "Gather.class.php";
require "ACF.class.php";

require "common.php";

/*
 * v100 while adding new features,
 * maintain old features:
 * but turn them on selectively
 * for testing new features
 *
 * v101/2 import images
 * v102/3 options 
 * v104 node import
 * v105 fields import (includes tags, images and content_types)
 */


$maxChunk = 1000000;
$init = true;

/* control options */
$options = new Options();
$options->setAll();

// $quiet 		= $options->get('quiet');
// $progress 	= $options->get('progress');

$s3bucket 	= $options->get('s3bucket');
$drupalPath = $options->get('drupalPath');
$imageStore = $options->get('imageStore');


$verbose    = $options->get('verbose');

$option = [];
$optionSet = ['defaults', 'help', 'quiet', 'verbose', 'progress', 'initialise' ,'files', 'nodes', 'taxonomy', 'fields'];
foreach ($optionSet as $opt) {
	$option[$opt] = $options->get($opt);
	if ($verbose) {
		$options->show($opt);
	}
}
$verbose = $option['verbose'];

//var_dump($option);die;
if ($options->get('defaults')) {
	$options->setDefaults();
}

if ($options->get('help')) {
	die("\nHELP Mode\n\n");
}

/* connect databases */
$wp = new DB('wp');
$d7 = new DB('d7');

$wp_taxonomy = new Taxonomy($wp, $verbose);
$d7_taxonomy = new Taxonomy($d7);
// If the wordpress instance of Taxonomy needs to get drupal data: 
$wp_taxonomy->setDrupalDb($d7);

if ($option['taxonomy']) {
		$wp_taxonomy->initialise($init);
		$vocabularies = $d7_taxonomy->getVocabulary();
		$taxonomyNames = [];
		$taxonomies = $d7_taxonomy->fullTaxonomyList();
		$wp_taxonomy->createTerms($taxonomies);
}

if ($option['files']) {
	$files = new Files($d7, $s3bucket, [
		'verbose' 	=> $verbose, 
		'quiet' 	=> $option['quiet'], 
		'progress' 	=> $option['progress']
	]);
	$files->setDrupalPath($drupalPath);
	$files->setImageStore($imageStore);
}

/* nodes */
$d7_node = new Node($d7);
$wp_post = new Post($wp);

/* content types ... */
$d7_fields = new Fields($d7);
$fieldSet = new FieldSet($d7);

$drupal_nodes = null;

if ($option['initialise']) {
	// build the term_taxonomy if not already present
	if ($wp_taxonomy->checkTerms()) {
		$wp_taxonomy->buildTerms();
	}
	$wp_post->purge();

}

if ($option['fields']) {
	$records = $fieldSet->getFieldData();
	$fieldTables = [];
	foreach($records as $key => $numberFound) {
		$fields = $fieldSet->getFieldData($key);
		foreach ($fields as $field) {
			$fieldTables[] = $key . '_' . $field;
		}
	}
}

// how many nodes to process?
$nodeCount = $d7_node->nodeCount();

if ($nodeCount > $maxChunk) {
	$chunk = floor($nodeCount / $maxChunk);
} else {
	$chunk = $nodeCount;
}

$d7_node->setNodeChunkSize($nodeCount);
$chunks = floor($nodeCount / $chunk);

// var_dump($chunk, $chunks);
// die;

if ($option['files'] && $verbose) {
	print "\nConverting $nodeCount Drupal nodes\n";
}

for ($c = 0; $c < $chunks; $c++) {

	$drupal_nodes = $d7_node->getNodeChunk();

	if ($drupal_nodes && count($drupal_nodes)) {

		foreach ($drupal_nodes as $node) {
			
			$wpPostId = null;

			if ($option['nodes']) {
				$d7_node->setNode($node);
				$wpPostId = $wp_post->makePost($node);
			}

			if ($option['files']) {
				$images = $files->getFiles($node->nid);
				if ($images) {
					$largest = null;

					foreach ($images as $image) {
						// get all sizes for this image
						$best = $files->getBestVersion($image->filename);

						if (!$option['quiet'] && !$option['progress'] && ($verbose === true || $files->isVerbose())) {
							print "\n" . $best->fid . ' ' . $best->type . ' ' . $best->filename . ' ' . $best->uri . "\n";
						}
					}
				}
			}

			if ($option['taxonomy']) {

				$taxonomies = $d7_taxonomy->nodeTaxonomies($node);
				if ($taxonomies && count($taxonomies)) {
					foreach ($taxonomies as $taxonomy) {
						$wp_taxonomy->makeWPTermData($taxonomy, $wpPostId);
						if ($verbose) {
							print "\n" . $taxonomy->category . ' : ' . $taxonomy->name;
						}
					}
// debug($taxonomies);
					if (!$option['quiet'] && !$option['progress'] && ($verbose === true) ) {
						print "\nImported " . count($taxonomies) . " taxonomies.\n";

					}				
				}
			}

			/* each node has a bunch of "fields" attached which can be additional content
			   for the content type and can be text fields, images. comments, tags
			*/
			if ($wpPostId && $option['fields']) {

				$acf = new ACF($wp);
				$acf->setPostId($wpPostId);

				// $d7_fields->setNodeId($node->nid);

				// $fields = $d7_fields->getFieldDataBody();
				// $images = $d7_fields->getFieldImages();
				// $tags   = $d7_fields->getFieldTags();
				// $comments = $d7_fields->getFieldComments();

				// // normal data types for FieldDataBody and Images, Tags, etc 
				// // should be already dealt with as content
				// if ($fields) {
				// 	switch($fields->bundle) {
				// 		case 'article':
				// 		break;
				// 		case 'blog':
				// 		break;
				// 		case 'podcast':
				// 		break;
				// 		case 'page':
				// 		break;
				// 		default: 
				// 			print "\n" . $fields->bundle;
				// 	}
				// }

				// check each field table for content types and make WP POSTMETA
				if ($fieldTables && count($fieldTables)) {

					foreach($fieldTables as $fieldDataSource) {

						$gather = new Gather($d7, $fieldDataSource);
						$gather->setNid($node->nid);

						$tableName = 'field_data_field_' . $fieldDataSource;
						$func = 'get_' . $fieldDataSource;
	
						$data = $gather->$func($node->nid);
						if ($data) {
							/* example of format for $data
							array(2) {
							  [0]=>   {TABLE}
							  string(15) "field_event_url"
							  [1]=>	  FIELDS {TABLE}_FIELDNAME => value
							  object(stdClass)#3929 (3) {
							    ["field_event_url_url"]=>
							    string(41) "http://www.telematicsupdate.com/cvtusa06/"
							    ["field_event_url_title"]=>
							    NULL
							    ["field_event_url_attributes"]=>
							    string(6) "a:0:{}"
							  }
							}
							*/
							// create Wordpress ACF data
							//var_dump($data);

						}
					}

				}
				// if ($images) {
				// 	var_dump($images);
				// }
				// if ($tags) {
				// 	var_dump($tags);
				// }
				// if ($comments) {
				// 	var_dump($comments);
				// }


			}

		}
	}
}

$wp->close();
$d7->close();

$wp_taxonomy->__destroy();

die("\n\nMigrator programme ends.\n\n");