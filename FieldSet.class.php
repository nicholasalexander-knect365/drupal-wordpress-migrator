<?php

require_once "FieldSetDiscovery.class.php";

class FieldSet extends FieldSetDiscovery {

	protected $tables;
	protected $bundle;
	protected $bundles;

	public function __construct($db) {
		$this->fieldsUsed = [];
		$this->tables  = [];
		$this->discovery($db);
	}

	public function getFieldData(String $label = 'labels') {

		list($fieldsUsed, $fieldTypesContent) = $this->findFieldTypesContent();

		// either list the field labels - i.e. content types
		if ($label === 'labels') {
			return $fieldsUsed;
 		} else {
			// or list the fields related to the field
			$combined = [];
			foreach ($fieldTypesContent as $pair) {
				$record = $pair[0];
				$field  = $pair[1];			
				if ($record === $label) {					
					$combined[] = $field;
				}
			}		
			return $combined;
		}
	}

	public function generate() {
		$this->setBundle('events');							
		$this->findTables();
	}
}