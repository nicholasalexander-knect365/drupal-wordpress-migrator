<?php

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
			$wp_postmeta = DB::wptable('postmeta');


			$terms_protect = [];
			$taxonomy_protect = [];

			// menu preservation
			$sql = "SELECT term_id, term_taxonomy_id FROM $wp_term_taxonomy WHERE taxonomy='nav_menu'";
			$menu_terms = $db->records($sql);
			foreach($menu_terms as $term) {
				$terms_protect[] = (integer)$term->term_id;
				$taxonomy_protect[] = (integer)$term->term_taxonomy_id;
			}
			$termset = implode(',', $terms_protect);
			$taxonomyset = implode(',', $taxonomy_protect);

			$sql = "DELETE FROM $wp_term_relationships WHERE term_taxonomy_id NOT IN ($taxonomyset)";
			$db->query($sql);
			$this->resetCounter($db, $wp_term_relationships);

			$sql = "DELETE FROM $wp_posts WHERE post_type <> 'nav_menu_item'";
			$db->query($sql);
			$this->resetCounter($db, $wp_posts);

			$sql = "DELETE FROM $wp_terms WHERE term_id NOT IN ($termset)";
			$db->query($sql);
			$this->resetCounter($db, $wp_terms);

			$sql = "DELETE FROM $wp_termmeta";
			$db->query($sql);
			$this->resetCounter($db, $wp_termmeta);

			$sql = "DELETE FROM $wp_term_taxonomy WHERE taxonomy <> 'nav_menu'";
			$db->query($sql);
			$this->resetCounter($db, $wp_term_taxonomy);

			$sql = "DELETE FROM $wp_postmeta";
			$db->query($sql);
			$this->resetCounter($db, $wp_postmeta);

		}
	}

}
