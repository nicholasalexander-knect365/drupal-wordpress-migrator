<?php

// backup the menu
require "../DB.class.php";
require "../WP.class.php";
require "../WPTerms.class.php";
require "../WPTermMeta.class.php";

require "../Initialise.class.php";
require "../Options.class.php";
//require "Post.class.php";
//require "WPTermMeta.class.php";
require "../User.class.php";
require "../Node.class.php";
require "../Post.class.php";
require "../Taxonomy.class.php";

// common routines including script init
require "../common.php";

/* nodes */
$d7_node = new Node($d7);
$wp_post = new Post($wp, $options);

// databases are now available as $wp and $d7
// $wordpress = new WP($wp, $options);

$nodes = $d7_node->getAllNodes();
$wp_terms = new WPTerms($wp, $options);
$wp_termmeta = new WPTermMeta($wp);
$wp_termmeta_term_id = $wp_termmeta->getTermMetaValue(DRUPAL_WP, DRUPAL_WP);

foreach($nodes as $node) {

	$nid = $node->nid;
	$type = $node->type;
	if ($type === 'media_entity' || $type === 'block_content' || $type === 'display_admin') {
		continue;
	}

	$wp_post_id =$wp_post->nodeToPost($nid);

//debug($nid . ' :: ' .$type . '>>>>>>>>>>>>>' . $wp_post_id);

	$sql = "SELECT field_penton_content_summary_value AS excerpt 
			FROM field_data_field_penton_content_summary 
			WHERE entity_id = $nid";
	$records = $d7->records($sql);

//debug(DB::strip($sql));

//debug($records);


	if (isset($records) && count($records) && $wp_post_id) {
		foreach ($records as $record) {

			$excerpt = w1250_to_utf8($record->excerpt);

			$sql = $wp_post->updatePost($wp_post_id, 'post_excerpt', $excerpt, true);
			print "\n$sql";
		}
	}
}
function w1250_to_utf8($text) {
    // map based on:
    // http://konfiguracja.c0.pl/iso02vscp1250en.html
    // http://konfiguracja.c0.pl/webpl/index_en.html#examp
    // http://www.htmlentities.com/html/entities/
    $map = array(
        chr(0x8A) => chr(0xA9),
        chr(0x8C) => chr(0xA6),
        chr(0x8D) => chr(0xAB),
        chr(0x8E) => chr(0xAE),
        chr(0x8F) => chr(0xAC),
        chr(0x9C) => chr(0xB6),
        chr(0x9D) => chr(0xBB),
        chr(0xA1) => chr(0xB7),
        chr(0xA5) => chr(0xA1),
        chr(0xBC) => chr(0xA5),
        chr(0x9F) => chr(0xBC),
        chr(0xB9) => chr(0xB1),
        chr(0x9A) => chr(0xB9),
        chr(0xBE) => chr(0xB5),
        chr(0x9E) => chr(0xBE),
        chr(0x80) => '&euro;',
        chr(0x82) => '&sbquo;',
        chr(0x84) => '&bdquo;',
        chr(0x85) => '&hellip;',
        chr(0x86) => '&dagger;',
        chr(0x87) => '&Dagger;',
        chr(0x89) => '&permil;',
        chr(0x8B) => '&lsaquo;',
        chr(0x91) => '&lsquo;',
        chr(0x92) => '&rsquo;',
        chr(0x93) => '&ldquo;',
        chr(0x94) => '&rdquo;',
        chr(0x95) => '&bull;',
        chr(0x96) => '&ndash;',
        chr(0x97) => '&mdash;',
        chr(0x99) => '&trade;',
        chr(0x9B) => '&rsquo;',
        chr(0xA6) => '&brvbar;',
        chr(0xA9) => '&copy;',
        chr(0xAB) => '&laquo;',
        chr(0xAE) => '&reg;',
        chr(0xB1) => '&plusmn;',
        chr(0xB5) => '&micro;',
        chr(0xB6) => '&para;',
        chr(0xB7) => '&middot;',
        chr(0xBB) => '&raquo;',
    );
    return html_entity_decode(mb_convert_encoding(strtr($text, $map), 'UTF-8', 'ISO-8859-2'), ENT_QUOTES, 'UTF-8');
}
