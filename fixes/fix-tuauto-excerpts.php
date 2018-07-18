<?php

// backup the menu
require "../DB.class.php";
require "../WP.class.php";

require "../Initialise.class.php";
require "../Options.class.php";
//require "Post.class.php";
//require "WPTermMeta.class.php";
require "../User.class.php";
require "../Node.class.php";
require "../Taxonomy.class.php";

// common routines including script init
require "../common.php";

/* nodes */
$d7_node = new Node($d7);
$wp_post = new Post($wp, $options);

// databases are now available as $wp and $d7
// $wordpress = new WP($wp, $options);

$nodes = $d7_node->getAllNodes();

foreach($nodes as $node) {
	$nid = $node->nid;
	$sql = "SELECT * FROM field_data_field_precis where entity_id = $nid";
	$records = $d7->records($sql);

	dd($records);
}
