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
	private $initialise_action = true;

	private $mapped = [
		'channels' 			=> 'category',
		'article type' 		=> 'type',
		'tags' 				=> 'tag',
		'itunes category' 	=> 'podcast',
		'weekly brief'		=> 'brief',
		'upload type'		=> 'upload'
	];

	private $termMeta = [
		'category' 	=> 'Category',
		'subject' 	=> 'Subject',
		'type'		=> 'Type',
		'region'	=> 'Region',
		'industry'	=> 'Industry'
	];


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


	public function initialise() {
		if ($this->termsAlreadyExist()) {
			if ($this->initialise_action) {
				$this->cleanUp();
			} else {
				return;
			}
		}
	}

	/** build Wordpress Terms **/
// 	public function buildTerms() {
// 		$taxonomies = ['subject', 'type', 'region', 'industry'];
// 		foreach($taxonomies as $taxonomy) {
// 			$sql = "INSERT into wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (null, 1, '$taxonomy', '', 0, 0)";
// var_dump($sql, $taxonomy);die;
// 			$this->db->query($sql);
// 		}	
// 	}

	static private function slugify($str) {
		$text = $str;
		// replace non letter or digits by -
		$text = preg_replace('~[^\pL\d]+~u', '-', $text);
		$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
		$text = preg_replace('~[^-\w]+~', '', $text);
		$text = trim($text, '-');
		$text = preg_replace('~-+~', '-', $text);
		$text = strtolower($text);
		if (empty($text)) {
			return '';
		}
		return $text;
	}

	private function cleanUp() {
		$sql = "DELETE FROM wp_terms WHERE term_id>1";
		$this->db->query($sql);
		$sql = "ALTER TABLE wp_terms AUTO_INCREMENT = 2";
		$this->db->query($sql);
		$sql = "DELETE FROM wp_term_taxonomy";
		$this->db->query($sql);
		$sql = "ALTER TABLE wp_term_taxonomy AUTO_INCREMENT = 1";
		$this->db->query($sql);
	}


	private function termsAlreadyExist() {
		$this->db->query('SELECT COUNT(*) as c from wp_terms');
		$item = $this->db->getRecord();
		if ($item->c > 1) {
			return true;
		}		
	}

	// private function categories($taxonomies) {
	// 	$catgories = [];
	// 	foreach($taxonomies as $taxonomy) {
	// 		if ((integer)$taxonomy->vid === $catId) {
	// 			$categories[$taxonomy->tid] = $taxonomy->name;
	// 		}
	// 	}
	// 	return $categories;
	// }

	private function remap($taxonomyType) {
var_dump($taxonomyType);
		return $this->mapped[strtolower($taxonomyType)];

	}

	// wp 
	public function createTerms($taxonomies) {

		foreach ($taxonomies as $taxonomy) {

			// create the wp_term from the taxonomy
			$slug = self::slugify($taxonomy->name);
			$name = addslashes(ucfirst($this->remap($taxonomy->name)));
			$vid  = $taxonomy->vid;

			$sql = "INSERT INTO wp_terms (name, slug, term_group) 
					VALUES ('$name', '$slug', $vid)";
			$this->db->query($sql);


//			$term_id = $this->db->lastInsertId();

		}
	}

	public function createTermTaxonomy($termTaxonomyData) {
		foreach ($termTaxonomyData as $term) {
			$term_id = $term->tid;
			$taxonomy = addslashes($term->type);
			$description = addslashes($term->name);
			$parent = $term->vid;
			
			$sql = "SELECT term_taxonomy_id FROM wp_term_taxonomy 
					WHERE term_id = $term_id";
			$this->db->query($sql);
			$record = $this->db->getRecord();
			if (isset($record)) {
				$id = $record->term_id;
				$sql = "UPDATE wp_term_taxonomy SET COUNT=COUNT+1 WHERE term_id=$id";
				$this->db->query($sql);
			} else {
				$sql = "INSERT INTO wp_term_taxonomy (term_id, taxonomy, description, parent, count) VALUES ($term_id, '$taxonomy', '$description', $parent, 0)";
				$this->db->query($sql);
			}
		}
	}

// 	private function updateTermTaxonomy($term_id, $taxonomy_type) {
// 		// DOES A TERM_TAXONOMY EXIST?
// 		$sql = "SELECT term_id, term_taxonomy_id,count 
// 				FROM wp_term_taxonomy 
// 				WHERE term_id=$term_id AND taxonomy='$taxonomy_type'";
// 		$this->db->query($sql);
// 		$record = $this->db->getRecord();
// 		if (count($record)) {
// 			$id = $record->term_id;
// 			$sql = "UPDATE wp_term_taxonomy SET COUNT=COUNT+1 WHERE term_id=$id";
// 			$this->db->query($sql);
// 		} else {
// 			$sql = "INSERT INTO wp_term_taxonomy 
// 				(term_id, taxonomy, description, parent, count) 
// 				VALUES 
// 				($term_id, '$taxonomy_type', 'Migrated from Drupal', 0, 0)";

// 			$this->db->query($sql);
// 		}	
// print "\n".$sql;	
// 	}


	public function makeWPTermTaxonomy($taxonomy_vocabulary) {

		foreach($taxonomy_vocabulary as $vocabulary) {
			$term = $vocabulary->machine_name;
			$sql = "SELECT term_id FROM wp_terms WHERE slug = '$term'";
			$this->db->query($sql);

			$wpTerms = $this->db->getRecords();
var_dump($wpTerms);die;
			foreach ($wpTerms as $term_id) {
				$this->updateTermTaxonomy($term_id, $taxonomy_type);
			}
		}
	}
// 	// termData is taxonomy term data = not terms!

// 	public function makeWPTermTaxonomyId($termData) {
// 		foreach($termData as $termTaxonomy) {

// 			$tid = $term->tid;
// 			$taxonomyType = $term->name;

// if (strlen($taxonomyType) > 32) {
// 	$shorter = substr($taxonomyType, 0, 32);
// 	print "\n\n" . $taxonomyType . ' :: trimmed to ' . "\n" . $shorter;
// 	$taxonomyType = $shorter;
// }

// 			$sql = "SELECT term_id FROM wp_terms where term_group = '$tid'";
// 			$this->db->query($sql);
// 			$wpTerm = $this->db->getRecord();
// 			$term_id = $wpTerm->term_id;


// 		}
// 	}


	public function taxonomyVocabulary() {
		$sql = "SELECT vid, name, machine_name, description, hierarchy, module, weight 
				FROM taxonomy_vocabulary 
				ORDER by vid";
		$this->db->query($sql);
		$data = $this->db->getRecords(); 
		return $data;	
	}

	public function taxonomyTermData() {
		$sql = "SELECT tid, vid, name, description FROM taxonomy_term_data ORDER BY tid";
		$this->db->query($sql);
		$taxonomyData = $this->db->getRecords(); 
		return $taxonomyData;
	}
	 /*
	 * get the Drupal taxonomyList
	 */
	public function fullTaxonomyList() {
		$taxonomyNames = [];
		$sql = 'SELECT distinct td.tid, td.vid, td.name, v.name AS type 
				FROM taxonomy_term_data td
                LEFT JOIN taxonomy_vocabulary v ON td.vid=v.vid';
		$this->db->query($sql);

		$records = $this->db->getRecords();

		return $records;
	}

	public function nodeVocabulary($node) {
		$taxonomies = [];
		$nid = $node->nid;
		$sql = "SELECT DISTINCT tv.vid, tv.machine_name FROM taxonomy_vocabulary tv 
				LEFT JOIN taxonomy_term_data td ON tv.vid=td.vid
				INNER JOIN taxonomy_index ti ON ti.tid=td.tid
				WHERE ti.nid=$nid";

		$this->db->query($sql);
		$tids = $this->db->getRecords();

		return $tids;
	}

	public function taxonomyTermDataForNode($node) {
		// find the taxonomies for this node
		$nid = $node->nid;
		$this->db->query("SELECT ti.nid, ti.tid, td.name, td.vid
			FROM taxonomy_index ti 
			LEFT JOIN taxonomy_term_data td ON td.tid=ti.tid 
			WHERE nid=$nid");
		$tids = $this->db->getRecords();	

		return $tids;
	}

	/** 
	 * taxonomyListWithHierarchy
	 * purpose: to return taxonomy_term_data with parent node
	 * NOT TESTTED
	 */
	public function taxonomyListWithHierarchy($node) {
		$nid = $node->nid;
		$this->db->query("SELECT td.tid, th.parent FROM taxonomy_term_hierarchy th
							LEFT JOIN taxonomy_term_data td on td.tid=th.tid
							LEFT JOIN taxonomy_vocabulary v on v.vid=td.vid
							where td.nid=$nid");
		$tids = $this->db->getRecords();
		return $tids;
	}

}