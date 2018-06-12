<?php

// ioti - DB options partial

	$this->siteId = 38;

	if (in_array('d', array_keys($options))) {

		if (isset($this->server) && $this->server === 'vm') {
			$this->setDefaults();
			$this->wordpressPath = '/var/www/public';
			$this->wordpressURL = 'http://tuauto.telecoms.local';
			$this->drupalPath = '/vagrant/drupal7/'.$this->project;
			return;

		} else if (isset($this->server) && $this->server === 'staging') {
			$this->setDefaults();
			$this->wordpressPath = '/srv/www/public';
			$this->wordpressURL = 'http://ioti.telecoms.local';
			$this->drupalPath = 'images';
			return;

		} else if (isset($this->server) && $this->server === 'local') {
			$this->setDefaults();
			$this->wordpressPath = '/home/nicholas/Dev/wordpress/' . $this->project;
			$this->wordpressURL = 'http://ioti.local';
			$this->drupalPath = '/home/nicholas/Dev/drupal7/' . $this->prject;
			return;

		} else {
			throw new Exception("\nServer " . $this->server . " not configured for -d default options");
		}
	}