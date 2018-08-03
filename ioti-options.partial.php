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
			$this->wordpressURL = 'http://www.iotworldtoday.com';
			$this->drupalPath = '/home/alexandern/ioti/files';
			return;

		} else if (isset($this->server) && $this->server === 'local') {
			$this->setDefaults();
			$this->wordpressPath = '/home/nicholas/Dev/wordpress/' . $this->project;
			$this->wordpressURL = $this->wordpressPath;
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
	} else {
		if (isset($this->server) && $this->server === 'local') {
			$this->wordpressPath = '/home/nicholas/Dev/wordpress/'.$this->server;
			if (empty($wordpressURL)) {
				$this->wordpressURL = 'http://ioti.multisite.local';
			}
		} else if (isset($this->server) && $this->server === 'staging') {
			$this->wordpressPath = '/srv/www/test2.telecoms.com';
			if (empty($wordpressURL)) {
				$this->wordpressURL = 'http://ioti.test2.telecoms.local';
			}
		} else if (isset($this->server) && $this->server === 'beta') {
			$this->wordpressPath = '/srv/www/test1.telecoms.com';
			if (empty($wordpressURL)) {
				$this->wordpressURL = 'http://ioti.test1.telecoms.com';
			}
		} else if (isset($this->server) && $this->server === 'multisite') {
			$this->wordpressPath = '/home/nicholas/Dev/wordpress/'.$this->server;
			if (empty($wordpressURL)) {
				$this->wordpressURL = 'http://ioti.multisite.local';
			}
		}
	}
