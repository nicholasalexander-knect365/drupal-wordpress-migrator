:<?php

include_once "DB.class.php";

class Initialise {

	private $db;
	private $options;
	public $menus;
	public $menuItems;

	public function __construct($db, $options) {
		$this->db = $db;
		$this->options = $options;
	}

	private function resetCounter($db, $table) {
		$sql = "SELECT COUNT(*) AS c FROM $table";
		$record = $db->record($sql);
		$recordCount = $record->c;
		$recordCount++;
		$sql = "ALTER TABLE $table AUTO_INCREMENT = $recordCount";
		$db->query($sql);
	}

	public function cleanUp($db) {

		if ($this->options->verbose) {
			print "\nCleaning up...";
		}

		if ($this->options->initialise) {

			$wp_posts = DB::wptable('posts');
			$wp_termmeta = DB::wptable('termmeta');
			$wp_term_taxonomy = DB::wptable('term_taxonomy');
			$wp_term_relationships = DB::wptable('term_relationships');
			$wp_terms = DB::wptable('terms');

			$sql = "DELETE $wp_term_relationships 
					FROM $wp_term_relationships 
					INNER JOIN $wp_posts on $wp_posts.ID=$wp_term_relationships.object_id
					WHERE $wp_posts.post_type <> 'nav_menu_item'";
			$db->query($sql);
			$this->resetCounter($db, $wp_term_relationships);

			$sql = "DELETE FROM $wp_posts 
					WHERE post_type <> 'nav_menu_item'";
			$db->query($sql);
			$this->resetCounter($db, $wp_posts);

			$sql = "DELETE $wp_terms 
					FROM $wp_terms 
					INNER JOIN $wp_term_taxonomy ON $wp_term_taxonomy.term_id=$wp_terms.term_id
					WHERE $wp_terms.term_id > 1 AND $wp_term_taxonomy.taxonomy <> 'nav_menu'";
			$db->query($sql);
			$this->resetCounter($db, $wp_terms);
// dd(DB::strip($sql));
			$sql = "DELETE $wp_termmeta 
					FROM $wp_termmeta 
					INNER JOIN $wp_term_taxonomy ON $wp_termmeta.term_id=$wp_term_taxonomy.term_id
					LEFT JOIN $wp_terms ON $wp_terms.term_id=$wp_termmeta.term_id
					WHERE $wp_term_taxonomy.taxonomy <> 'nav_menu'";
			$db->query($sql);
			$this->resetCounter($db, $wp_termmeta);

			$sql = "DELETE FROM $wp_term_taxonomy WHERE taxonomy <> 'nav_menu'";
			$db->query($sql);
			$this->resetCounter($db, $wp_term_taxonomy);

		}
	}

}