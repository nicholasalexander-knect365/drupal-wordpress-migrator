<?php

require "DB.class.php";

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
 * v101 import nodes and images 
 *
 */
$imports = ['initial' => true,
			'nodes'	=> true,
			'files' => true,
			'taxonomy' => true,
			'events'	=> false
];

$init = true;

if (count($argv) > 1) {
	$verbose = in_array('-v', $argv);
	$quiet = in_array('-q', $argv);
	$progress = in_array('-p', $argv);
	$help = in_array('-?', $argv);
} else {
	$verbose = $quiet = $help = $progress = false;
}

if ($help) {
	die( "\nFormat: php " . $argv[0] . " [-q, -p or -v]\n\n");
}
if ($progress) {
	$verbose = '.';
}

$wp = new DB('wp');
$d7 = new DB('d7');

$wp_taxonomy = new Taxonomy($wp, $verbose);
$d7_taxonomy = new Taxonomy($d7);

$files = new Files($d7, 'http://pentontuautodrupalfs.s3.amazonaws.com', ['verbose' => $verbose, 'quiet' => $quiet, 'progress' => $progress]);

// TODO: pass files path on CLI ?
$files->setDrupalPath('../drupal7/tuauto');
// TODO: ensure target exists, and is empty (?)
$files->imageTarget = 'images/';


$d7_events = new Events($d7);

$d7_node = new Node($d7);
$drupal_nodes = null;

if ($imports['initial']) {
	// build the term_taxonomy if not already present
	if ($wp_taxonomy->checkTerms()) {
		$wp_taxonomy->buildTerms();
	}
}

// look at each node
if (!$drupal_nodes) {
	$d7->query('SELECT * FROM `node`');
	$drupal_nodes = $d7->getRecords();
}

if ($wp_taxonomy->isVerbose()) {
	print "\nProcessing " . count($drupal_nodes) . " Drupal nodes";
}

if ($imports['taxonomy']) {
		$wp_taxonomy->initialise($init);
		$vocabularies = $d7_taxonomy->getVocabulary();
		$taxonomyNames = [];
		$taxonomies = $d7_taxonomy->fullTaxonomyList();

		// wp_terms
		$wp_taxonomy->createTerms($taxonomies);
}

foreach($drupal_nodes as $node) {

	if ($imports['nodes']) {

		// get the data
		// latest revision
		// all revisions?

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



$wp->close();
$d7->close();

$wp_taxonomy->__destroy();
