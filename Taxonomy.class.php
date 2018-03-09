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
	private $mapped = [
		'channels' 			=> 'category',
		'article type' 		=> 'type',
		'tags' 				=> 'tag',
		'itunes category' 	=> 'podcast',
		'weekly brief'		=> 'brief'
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
	

	private function cleanUp() {
		$sql = "DELETE FROM wp_terms WHERE term_id>1";
		$this->db->query($sql);
		$sql = "DELETE FROM wp_term_taxonomy";
		$this->db->query($sql);
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
		return $this->mapped[strtolower($taxonomyType)];

	}
	public function createTerms($taxonomies) {

		if ($this->termsAlreadyExist()) {
			return;
		}

		foreach ($taxonomies as $taxonomy) {
//var_dump($taxonomy);die;
			$category = addslashes(ucfirst($taxonomy->name));
			$slug = self::slugify($category);
			$taxonomyType = $this->remap($taxonomy->type);


			$sql = "INSERT INTO wp_terms (name, slug) VALUES ('$category', '$slug')";
			$this->db->query($sql);
			$term_id = $this->db->lastInsertId();

			$sql = "INSERT INTO wp_term_taxonomy (term_id, taxonomy, description, parent, count) VALUES ($term_id, '$taxonomyType', 'Migrated from Drupal', 0, 0)";

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