<?php

class WPTerms {
	
	public $options;

	public function __construct($db, $options) {
		$this->db = $db;
		$this->options = $options;
	}

	/*** 
	 * tests if blank slugs are used 
	 */
	public function testBlankSlugs() {
		
		$wp_terms = DB::wptable('terms');
		$wp_term_taxonomy = DB::wptable('term_taxonomy');

		$sql = "SELECT * FROM $wp_terms WHERE slug IS NULL OR slug = ''";
		$records = $this->db->records($sql);
		if ($records) {
			foreach($records as $term) {
				// is there a term_taxonomy for it?
				$term_id = $term->term_id;
				$sql = "SELECT term_taxonomy_id FROM $wp_term_taxonomy WHERE term_id = $term_id";
				$taxonomies = $this->db->record($sql);
				if (count ((array)$taxonomies)) {
					return false;
				}
			}
		}
		return true;
	}

	public function removeBlankSlugs() {

		$wp_terms = DB::wptable('terms');
		
		$sql = "DELETE FROM $wp_terms WHERE slug IS NULL OR slug = ''";
		$this->db->query($sql);

		debug("Removed ");

	}

}