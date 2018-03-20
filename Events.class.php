<?php

require_once "DB.class.php";

class Events {

	public $db;

	public function __construct($db) {
		$this->db = $db;
	}

	private $tables = [
		'field_revision_field_event_history',
		'field_revision_field_event_date',
		'field_revision_field_event_url',
		'field_revision_field_event_location',
		'field_revision_field_event_organiser',
		'field_revision_field_event_recording_url',
		'field_revision_field_event_speakers_url'
	];

	public function getEvents($node) {

		$nid = $node->nid;
		$records = [];

		foreach ($this->tables as $table) {
			$sql = "SELECT * FROM `$table`
					WHERE entity_type= 'node' AND entity_id=$nid";

			$result = $this->db->query($sql);
			if ($result) {
				$records[] = $this->db->getRecords();
			}
		}
		// if (count($records)) {
		// 	var_dump($records);
		// 	die;
		// }
		return $records;
	}

}