<?php 

require_once "DB.class.php";

class PostMeta extends DB {

	public $db;
	public $wp_postmeta;
	private $debugDetail;

	public function __construct($db, $table) {
		$this->wp_postmeta = $table;
		$this->db = $db;
		$this->debugDetail = true;
	}

	private function convert_metakey($key) {

		switch ($key) {

			case 'event_url_url':
			case 'event_brochure_url_url':
				$key = 'url';
				break;
			
			case 'event_organiser_value':
				$key = 'event_organiser';
				break;

			case 'event_organiser_email_email':
				$key = 'event_organiser_email';
				break;

			case 'event_date_value':
			case 'unixstartdate':
				$key = 'start_date';
				break;

			case 'event_date_value2':
			case 'date_to':
				$key = 'end_date';
				break;
			
			case 'event_attendees_value':
				$key = 'event_attendees';
				break;

			case 'event_location_value':
				$key = 'event_location';
				break;

			case 'event_brochure_url_url':
				$key = 'event_brochure_url';
				break;

			case 'event_programme_url_url';
				$key = 'event_programme_url';
				break;

			case 'event_venue_value':
				$key = 'venue';
				break;

			case 'event_history_value':
				$key = 'event_history';

			case 'report_url': 
				$key = 'report_url';
				break;
			
			case 'report_teaser_format': 
				$key = 'teaser_format';
				break;

			case 'report_teaser_value': 
				$key = 'teaser_value';
				break;

			default: 
				// debug($key);
				break;
		}
		return $key;
	}

	public function createGetPostMeta($post_id, $key, $value) {

		$wp_postmeta = DB::wptable('postmeta');
		$sql = "SELECT meta_id FROM $wp_postmeta WHERE post_id = $post_id AND meta_key = '$key'";
		$record = $this->db->record($sql);
		if ($record) {
			return $record->meta_id;
		}

		$sql = "INSERT INTO $wp_postmeta (post_id, meta_key, meta_value) VALUES ($post_id, $key, $value)";
		$this->db->query($sql);
		$id = $this->db->lastInsertId();
		return $id;
	}

	public function createUpdatePostMeta($postId, $key, $value) {

		$wp_postmeta = DB::wptable('postmeta');

		$sql = "SELECT meta_id FROM $wp_postmeta WHERE post_id=$postId AND meta_key='$key'";
		$record = $this->db->record($sql);

		if (isset($record) && $record->meta_id) {
			$meta_id = $record->meta_id;
			$sql = "UPDATE $wp_postmeta SET meta_value='$value' WHERE meta_id=$meta_id";
		} else {
			$sql = "INSERT INTO $wp_postmeta (post_id, meta_key, meta_value) VALUES ($postId, '$key', '$value')";
		}
//debug($sql);
		$this->db->query($sql);
	}

	// wordpress entities create
	public function createFields($wpPostId, $data) {

		$wp_postmeta = $this->wp_postmeta;

		foreach($data as $key => $value) {

			$key = $this->convert_metakey($key);

			if ($key === 'start_date' || $key === 'end_date') {
				//$value = date($value, 'U');
				$start_date = $value;
			}
			$value = $this->db->prepare($value);

			if ($key === 'end_date' && !strlen($value) && strlen($start_date)) {
				$end_date = $start_date;
			} else if ($key === 'end_date' && strlen($value)) {
				$end_date = $value;
			}

			if (!strlen($value)) {
				$value = '-';
			}

			$sql = "SELECT meta_id FROM $wp_postmeta WHERE meta_key = '$key' AND post_id=$wpPostId";
			$record = $this->db->record($sql);

			if ($record && $record->meta_id) {
				$meta_id = $record->meta_id;
				$sql = "UPDATE $wp_postmeta SET meta_value = '$value' WHERE meta_id=$meta_id";
				
				$this->db->query($sql);
			} else {

				$sql = "INSERT INTO $wp_postmeta (post_id, meta_key, meta_value) VALUES ($wpPostId, '$key', '$value')";
				
				$this->db->query($sql);
				$meta_id = $this->db->lastInsertId();
			}

			assert($meta_id > 0);
		}
	}
}