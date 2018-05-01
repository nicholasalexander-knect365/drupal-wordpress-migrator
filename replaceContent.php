<?php

require "DB.class.php";
require "WP.class.php";

require "Initialise.class.php";
require "Options.class.php";
require "Post.class.php";
// require "PostMeta.class.php";
require "WPTermMeta.class.php";
require "User.class.php";

require "Node.class.php";
// require "Files.class.php";
// require "Taxonomy.class.php";

// require "Fields.class.php";
// require "FieldSet.class.php";
// require "Gather.class.php";
//require "ACF.class.php";

$maxChunk = 1000000;

require "common.php";

define('DRUPAL_WP', 'DRUPAL_WP');

/* control options */
try {
	$options = new Options();
	$options->setAll();

	$project 	= $options->get('project');
	$drupalPath = $options->get('drupalPath');
	$wordpressPath = $options->get('wordpressPath');
	$server 	= $options->get('server');
	$verbose    = $options->get('verbose');

	$option = [];

	foreach ($options->all as $opt) {
		$option[$opt] = $options->get($opt);
	}
	$options->showAll();

	if ($options->get('help')) {
		die("\nHELP Mode\n\n");
	}
} catch (Exception $e) {
	debug("Option setting error\n" . $e->getMessage() . "\n\n");
	die;
}


/* connect databases */
try {
	$wp = new DB($server, 'wp', $options);
	$d7 = new DB($server, 'd7', $options);
} catch (Exception $e) {
	die( 'DB connection error: ' . $e->getMessage());
}

// configure the wordpress environment
$wp->configure($options);
$d7->configure($options);

$wordpress = new WP($wp, $options);

/* nodes */
$d7_node = new Node($d7);
$wp_post = new Post($wp);

$wp_termmeta = new WPTermMeta($wp);
$wp_termmeta_term_id = $wp_termmeta->getSetTerm(DRUPAL_WP, 'Drupal Node ID');

// how many nodes to process?
$nodeCount = $d7_node->nodeCount();

if ($nodeCount > $maxChunk) {
	$chunk = floor($nodeCount / $maxChunk);
} else {
	$chunk = $nodeCount;
}

$d7_node->setNodeChunkSize($nodeCount);
$chunks = floor($nodeCount / $chunk);

// set a value ONLY for a test version that only does a few posts
$TESTLIMIT = null;




for ($c = 0; $c < $chunks; $c++) {

	$drupal_nodes = $d7_node->getNodeChunk($TESTLIMIT);
	print "\nReplacing " . count($drupal_nodes). " post contents";

	if (isset($drupal_nodes) && count($drupal_nodes)) {

		foreach ($drupal_nodes as $node) {

//debug($node);

			$wpPostId = $wp_termmeta->getTermMetaValue($wp_termmeta_term_id, $node->nid);

			//$wpPostId = $wp_post->makePost($node, $options, $files, $options->imageStore, $users);
			$wp_post->replacePostContent($wpPostId, $node);
			if ($node->nid % 10 === 0) {
				print '.';
			}
		}
	}
}

print "\nCompleted\n\n";

$wp->close();
$d7->close();
