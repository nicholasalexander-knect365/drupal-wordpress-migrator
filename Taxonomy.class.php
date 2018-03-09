<?php
/**
 * Taxonomy class
 * for translating D7 to Wordpress
 * taxonomies: dual purpose class
 * call with DB constructor
 */
require_once "DB.class.php";

class Taxonomy {
	
	public $db;

	public function __construct($db) {
		$this->db = $db;
	}
	
	/** 
	 * checkTerms in Wordpress
	 */
	public function checkTerms() {
		$sql = "SELECT COUNT(*) AS c FROM wp_term_taxonomy";
		$this->db->query($sql);
		$items = $this->db->getRecord();
		return ($items->c === 1);
	}

	/** build Wordpress Terms **/
	public function buildTerms() {
		$taxonomies = ['subject', 'type', 'region', 'industry'];
		foreach($taxonomies as $taxonomy) {
			$sql = "INSERT into wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (null, 1, '$taxonomy', '', 0, 0)";
			$this->db->query($sql);
		}	
	}

	/** 
	 * get the Drupal taxonomyList
	 */
	public function taxonomyList() {
		$taxonomyNames = [];
		$sql = 'SELECT distinct td.tid, td.vid, td.name, v.name as type  FROM taxonomy_term_data td  LEFT JOIN taxonomy_vocabulary v ON td.vid=v.vid';
		$this->db->query($sql);
//die($sql);
		$records = $this->db->getRecords();

		return $records;
	}

	public function taxonomyListForNode($node) {
		// find the taxonomies for this node
		$this->db->query("SELECT nid, tid FROM taxonomy_index WHERE nid=" . $node->nid);
		$tids = $this->db->getRecords();		
		return $tids;
	}

	public function termData($tid) {
		$this->db->query("SELECT * FROM taxonomy_term_data where tid=" . $tid);
		$termData = $this->db->getRecords();
		return $termData;
	}

}