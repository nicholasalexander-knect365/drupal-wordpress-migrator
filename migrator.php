<?php

require "DB.class.php";
require "Events.class.php";
require "Node.class.php";
require "Taxonomy.class.php";

/*
 * while adding new features,
 * maintain old features:
 * but turn them on selectively
 * for testing new features
 */
$imports = ['initial' => true,
			'taxonomy' => true,
			'events'	=> false
];
$init = true;

$wp = new DB('wp');
$d7 = new DB('d7');

$wp_taxonomy = new Taxonomy($wp);
$d7_taxonomy = new Taxonomy($d7);

$d7_events = new Events($d7);

$d7_node = new Node($d7);

if ($imports['initial']) {
	// build the term_taxonomy if not already present
	if ($wp_taxonomy->checkTerms()) {
		$wp_taxonomy->buildTerms();
	}
}

if ($imports['taxonomy']) {
	$wp_taxonomy->initialise($init);
	$vocabularies = $d7_taxonomy->getVocabulary();
	$taxonomyNames = [];
	$taxonomies = $d7_taxonomy->fullTaxonomyList();

	// wp_terms
	$wp_taxonomy->createTerms($taxonomies);

	$d7->query('SELECT * FROM `node`');
	$drupal_nodes = $d7->getRecords();

	if ($wp_taxonomy->isVerbose()) {
		print "\nProcessing " . count($drupal_nodes) . " Drupal nodes";
	}

	foreach ($drupal_nodes as $node) {

		if ($imports['taxonomy']) {
			$taxonomies = $d7_taxonomy->nodeTaxonomies($node);
			foreach ($taxonomies as $taxonomy) {
				$wp_taxonomy->makeWPTermData($taxonomy);
			}
		}

		if ($imports['events']) {
			$events = $d7_events->getEvents($node);

			if ($events && count($events)) {

				assert($events !== NULL && count($events));

var_dump($events);

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



$wp->close();
$d7->close();

$wp_taxonomy->__destroy();
