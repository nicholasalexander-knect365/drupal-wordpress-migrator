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

	// public function preserveMenuTaxonomy() {

	// 	$wp_terms = DB::wptable('terms');
	// 	$wp_posts = DB::wptable('posts');
	// 	$wp_term_taxonomy = DB::wptable('term_taxonomy');

	// 	$sql = "SELECT * FROM $wp_terms AS t 
	// 			LEFT JOIN $wp_term_taxonomy AS tt ON tt.term_id = t.term_id
 // 				WHERE tt.taxonomy = 'nav_menu'";
 // 		$this->menus = $this->db->records($sql);
	// }

	// public function createMenuTaxonomy() {
		
	// 	$wp_terms = DB::wptable('terms');
	// 	$wp_posts = DB::wptable('posts');
	// 	$wp_term_taxonomy = DB::wptable('term_taxonomy');

	// 	if (isset($this->menus) && count($this->menus)) {
	// 		foreach ($this->menus as $menu) {
	// 			$sqlStr = "INSERT INTO $wp_term_taxonomy (term_id, taxonomy, description, parent, count) VALUES (%d, %s, %s, %d, %d)";
	// 			$sql = sprintf($sqlStr, $menu->term_id, $menu->taxonomy, '', $menu->parent, $menu->count);
	// 			$this->db->query($sql);

	// 			$sqlStr = "INSERT INTO $wp_terms (name, slug, term_group) VALUES (%s, %s, %d)";
	// 			$sql = sprintf($sqlStr, $menu->name, $menu->slug, $menu->term_group);
	// 			$this->db->query($sql);
	// 		}
	// 	}
	// }

	private function resetCounter($db, $table) {
		$sql = "SELECT COUNT(*) AS c FROM $table";
		$record = $db->record($sql);
		$recordCount = $record->c;
		$recordCount++;
		$sql = "ALTER TABLE $table AUTO_INCREMENT = $recordCount";
		$db->query($sql);
debug($sql);
	}

	public function cleanUp($db) {

		if ($this->options->verbose) {
			print "\nCleaning up...";
		}
		// full initialise clears everything
		if ($this->options->initialise) {

			// preserve menus
			//$this->preserveMenuTaxonomy();

// 			$sql = "SELECT p.* 
// 					FROM wp_posts AS p 
// 					LEFT JOIN wp_term_relationships AS tr ON tr.object_id = p.ID
// 					LEFT JOIN wp_term_taxonomy AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
// 					WHERE p.post_type = 'nav_menu_item' 
// 					  AND tt.taxonomy = 'nav_menu'";
// dd(DB::strip($sql));
//					  AND tt.term_id = termIdFortheMenus...";
			$wp_posts = DB::wptable('posts');
			$wp_termmeta = DB::wptable('termmeta');
			$wp_term_taxonomy = DB::wptable('term_taxonomy');
			$wp_term_relationships = DB::wptable('term_relationships');
			$wp_terms = DB::wptable('terms');

			// //$sql = "DELETE $wp_term_relationships 
			// $sql = "SELECT *
			// 		FROM $wp_term_relationships tr
			// 		LEFT JOIN $wp_posts p ON p.ID=tr.object_id
			// 		LEFT JOIN $wp_term_taxonomy tt on tt.term_taxonomy_id=tr.term_taxonomy_id
			// 		WHERE p.post_type <> 'nav_menu_item'";

			$sql = "DELETE $wp_term_relationships 
					FROM $wp_term_relationships 
					INNER JOIN $wp_posts on $wp_posts.ID=$wp_term_relationships.object_id
					WHERE $wp_posts.post_type <> 'nav_menu_item'";



			$db->query($sql);

			$this->resetCounter($db, $wp_term_relationships);

			// $sql = "SELECT COUNT(*) AS c FROM $wp_term_relationships";
			// $record = $db->record($sql);
			// $recordCount = $record->c;
			// $recordCount++;
			// $sql = "ALTER TABLE $wp_term_relationships AUTO_INCREMENT = $recordCount";
			// $db->query($sql);

			$sql = "DELETE FROM $wp_posts WHERE post_type <> 'nav_menu_item'";
			$db->query($sql);

			$this->resetCounter($db, $wp_posts);

			// $sql = "SELECT COUNT(*) AS c FROM $wp_posts";
			// $record = $db->record($sql);
			// $recordCount = $record->c;
			// $recordCount++;

			// $sql = "ALTER TABLE $wp_posts AUTO_INCREMENT = $recordCount";
			// $db->query($sql);

			$sql = "DELETE $wp_terms 
					FROM $wp_terms t
					INNER JOIN $wp_term_taxonomy tx ON tx.term_id=t.term_id
					WHERE t.term_id>1 AND tx.taxonomy <> 'nav_menu'";
			$db->query($sql);
			$this->resetCounter($db, $wp_terms);

			$sql = "DELETE $wp_termmeta 
					FROM $wp_termmeta tm
					INNER JOIN $wp_term_taxonomy tx on tm.term_id=tx.term_id
					LEFT JOIN $wp_terms t ON t.term_id=tm.term_id
					WHERE tx.taxonomy <> 'nav_menu'";
			$db->query($sql);
			$this->resetCounter($db, $wp_termmeta);

			$sql = "DELETE FROM $wp_term_taxonomy WHERE taxonomy <> 'nav_menu";
			$db->query($sql);
			$this->resetCounter($db, $wp_term_taxonomy);
			// $sql = "SELECT COUNT(*) AS c FROM $wp_term_taxonomy";
			// $record = $db->record($sql);
			// $recordCount = $record->c;
			// $recordCount++;
			// $sql = "ALTER TABLE $wp_term_taxonomy AUTO_INCREMENT = $recordCount";
			// $db->query($sql);

			// reestablish menus
			//$this->createMenuTaxonomy();

		}
	}

}