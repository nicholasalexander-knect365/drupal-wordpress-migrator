<?php

require_once "DB.class.php";

class Events {

	public $db;

	public function __construct($db) {
		$this->db = $db;
		die('EVENTS class deprecated!');
	}

	private $tables = [
		'field_data_field_event_attendees',
		'field_data_field_event_brochure_url',
		'field_data_field_event_date',
		'field_data_field_event_history',
		'field_data_field_event_location',
		'field_data_field_event_organiser',
		'field_data_field_event_organiser_email',
		'field_data_field_event_programme_url',
		'field_data_field_event_recording_url',
		'field_data_field_event_speakers_url',
		'field_data_field_event_event_url',
	];

	private function eventQuery($table, $node) {
		$nid = $node->nid;
		$sql = "SELECT * FROM `$table`
				WHERE entity_type= 'node' AND entity_id=$nid";

		$records = [];
		$result = $this->db->query($sql);
		// not all tables exist - only accumulate records when there are results	
		if ($result) {
			$records = $this->db->getRecords();
		}
// if (count($records)) {
// 	var_dump(DB::strip($sql), $records);
// }
		return $records;
	}

	public function getEvents($node) {

		$events = [];
		$revision_events = [];

		foreach ($this->tables as $table) {
			$event = $this->eventQuery($table, $node);
			if (count($event)) {
				$events[] = $event;
			}
			
			$revision = preg_replace('/field_data/', 'field_revision', $table);		
			$revision_event = $this->eventQuery($revision, $node);
			if (count($revision_event)) {
				$revision_events[] = $revision_event;
			}

		}
// if (count($events)) {
// 	var_dump($events, $revision_events);
// }
$records = array_merge($events, $revision_events);
		return $records;
	}

}