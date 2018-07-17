<?php

// ioti - DB options partial

	$this->siteId = 38;

	if (in_array('d', array_keys($options))) {

		if (isset($this->server) && $this->server === 'vm') {
			$this->setDefaults();
			$this->wordpressPath = '/var/www/public';
			$this->wordpressURL = 'http://ioti.telecoms.local';
			$this->drupalPath = '/vagrant/drupal7/'.$this->project;
			return;

		} else if (isset($this->server) && $this->server === 'staging') {
			$this->setDefaults();
			$this->wordpressPath = '/srv/www/test2.telecoms.com';
			$this->wordpressURL = 'http://ioti.test2.telecoms.com';
			$this->drupalPath = '/home/alexandern/ioti/files';
			return;
		} else if (isset($this->server) && $this->server === 'beta') {
			$this->setDefaults();
			$this->wordpressPath = '/srv/www/test1.telecoms.com';
			$this->wordpressURL = 'http://iotworldtoday.com';
			$this->drupalPath = 'images';
			return;

		} else if (isset($this->server) && $this->server === 'local') {
			$this->setDefaults();
			$this->wordpressPath = '/home/nicholas/Dev/wordpress/' . $this->project;
			$this->wordpressURL = 'http://ioti.local';
			$this->drupalPath = '/home/nicholas/Dev/drupal7/' . $this->project;
			return;

		} else if (isset($this->server) && $this->server === 'multisite') {
			$this->setDefaults();
			$this->wordpressPath = '/home/nicholas/Dev/wordpress/' . $this->server;
			$this->wordpressURL = 'http://ioti.multisite.local';
			$this->drupalPath = '/home/nicholas/Dev/drupal7/' . $this->project;
			return;

		} else {
			throw new Exception("\nServer " . $this->server . " not configured for -d default options");
		}
	}
