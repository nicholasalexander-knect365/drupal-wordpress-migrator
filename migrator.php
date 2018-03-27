<?php

require "DB.class.php";
require "Options.class.php";
require "Post.class.php";

require "Node.class.php";
require "Files.class.php";
require "Taxonomy.class.php";
require "Events.class.php";

/*
 * v100 while adding new features,
 * maintain old features:
 * but turn them on selectively
 * for testing new features
 *
 * v101/2 import images
 * v102/3 options 
 * v104 node import
 *
 */
$imports = ['initial' 	=> false,
			'nodes'		=> false,
			'files' 	=> false,
			'taxonomy' 	=> true,
			'events'	=> false
];

$maxChunk = 1000000;
$init = true;

/* control options */
$options = new Options();
$options->setAll();
$quiet 		= $options->get('quiet');
$progress 	= $options->get('progress');
$verbose 	= $options->get('verbose');
$s3bucket 	= $options->get('s3bucket');
$drupalPath = $options->get('drupalPath');
$imageStore = $options->get('imageStore');


/* connect databases */
$wp = new DB('wp');
$d7 = new DB('d7');
$wp_taxonomy = new Taxonomy($wp, $verbose);
$d7_taxonomy = new Taxonomy($d7);
if ($imports['taxonomy']) {
		$wp_taxonomy->initialise($init);
		$vocabularies = $d7_taxonomy->getVocabulary();
		$taxonomyNames = [];
		$taxonomies = $d7_taxonomy->fullTaxonomyList();
		$wp_taxonomy->createTerms($taxonomies);
}

if ($imports['files']) {
	$files = new Files($d7, $s3bucket, [
		'verbose' 	=> $verbose, 
		'quiet' 	=> $quiet, 
		'progress' 	=> $progress
	]);
	$files->setDrupalPath($drupalPath);
	$files->setImageStore($imageStore);
}
/* content types ... */
$d7_events = new Events($d7);

/* nodes */
$d7_node = new Node($d7);
$wp_post = new Post($wp);

$drupal_nodes = null;


if ($imports['initial']) {
	// build the term_taxonomy if not already present
	if ($wp_taxonomy->checkTerms()) {
		$wp_taxonomy->buildTerms();
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

if ($verbose) {
	print "\nConverting $nodeCount Drupal nodes\n";
}

for ($c = 0; $c < $chunks; $c++) {

	$drupal_nodes = $d7_node->getNodeChunk();

	if ($drupal_nodes && count($drupal_nodes)) {

		foreach ($drupal_nodes as $node) {

			if ($imports['nodes']) {
				$d7_node->setNode($node);
				$wp_post->makePost($node);
			}

			if ($imports['files']) {
				$images = $files->getFiles($node->nid);
				if ($images) {
					$largest = null;

					foreach ($images as $image) {
						// get all sizes for this image
						$best = $files->getBestVersion($image->filename);

						if (!$quiet && !$progress && ($verbose === true || $files->isVerbose())) {
							print "\n" . $best->fid . ' ' . $best->type . ' ' . $best->filename . ' ' . $best->uri . "\n";
						}
					}
				}
			}

			if ($imports['taxonomy']) {

				$taxonomies = $d7_taxonomy->nodeTaxonomies($node);
				if ($taxonomies && count($taxonomies)) {
					foreach ($taxonomies as $taxonomy) {
						$wp_taxonomy->makeWPTermData($taxonomy);
					}
				
					if (!$quiet && !$progress && ($verbose === true) ) {
						print "\nImported " . count($taxonomies) . "taxonomies.\n";
					}				
				}
			}
			if ($imports['events']) {
				$events = $d7_events->getEvents($node);

				if ($events && count($events)) {

					assert($events !== NULL && count($events));

		//var_dump($events);

					foreach ($events as $event) {
						foreach($event as $component) {
							$event_node_id = $component->entity_id;
							$event_vid = $component->revision_id;

							$node = $d7_node->getNode($event_node_id);
							$compare = $d7_node->getNode($event_node_id, $event_vid);

							if ($node != $compare) {
								print "\n--------------------------------\n";
								var_dump($node, $compare);
							}

						}
					}
				}

			}

		}
	}
}



$wp->close();
$d7->close();

$wp_taxonomy->__destroy();
