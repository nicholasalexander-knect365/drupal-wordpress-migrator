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

      $sql = "DELETE FROM $wp_term_relationships";
      $db->query($sql);
      $this->resetCounter($db, $wp_term_relationships);

      $sql = "DELETE FROM $wp_posts WHERE post_type <> 'nav_menu_item'";
      $db->query($sql);
      $this->resetCounter($db, $wp_posts);

      $sql = "DELETE FROM $wp_terms";
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