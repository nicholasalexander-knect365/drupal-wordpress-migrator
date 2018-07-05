<?php 
// tu-auto specific options
$this->wordpressPath = isset($options['wordpressPath']) ? $options['wordpressPath'] : '';
// default option
if (in_array('d', array_keys($options))) {

	$this->server = $options['server'];
	if (isset($this->server) && $this->server === 'vm') {
		$this->setDefaults();
		$this->wordpressPath = '/var/www/public';
		$this->wordpressURL = 'http://tuauto.telecoms.local';
		$this->drupalPath = '/vagrant/drupal7/tu-auto';
		return;

	} else if (isset($this->server) && $this->server === 'vm2') {
		$this->setDefaults();
		$this->wordpressPath = '/home/vagrant/Code/client/k365/wp';
		$this->wordpressURL = 'http://tuauto.local';
		$this->drupalPath = '/home/vagrant/Code/client/k365/tu-auto';
		return;

	} else if (isset($this->server) && $this->server === 'beta') {
		$this->setDefaults();
		$this->wordpressPath = '/srv/www/test1.telecoms.com';
		$this->wordpressURL = 'http://beta.tu-auto.com';
		$this->drupalPath = '/srv/www/test1.telecoms.com/drupal7/tu-auto';
		return;

	} else if (isset($this->server) && $this->server === 'staging') {
		$this->setDefaults();
		$this->wordpressPath = '/srv/www/test2.telecoms.com';
		$this->wordpressURL = 'www.tu-auto.com';
		$this->drupalPath = '/srv/www/test2.telecoms.com/migrator/drupal7/tu-auto';
		return;

		throw new Exception("\n-d default mode not available on staging:\n\nSuggest command line like:\n\nphp migrator.php --wordpressPath=/srv/www/test1.telecoms.com --project=tuauto --clean --drupalPath=/srv/www/test1.telecoms.com/drupal7/tu-auto --server=staging --wordpressURL=http://beta-tu.auto.com -n -u -t -f --acf");

	} else {

		// first: check it is NOT staging!  - this is local (developer)
		if (getcwd() === '/home/nicholas/Dev/migrator') {
			$this->setDefaults();
			$this->wordpressPath = '/home/nicholas/Dev/wordpress/tuauto';
			$this->wordpressURL = 'http://tuauto.local';
			$this->drupalPath = '/home/nicholas/Dev/drupal7/tu-auto';
			return;
		} else {
			throw new Exception('Please do not use default mode on this server without --server indication');
		}
	}
}
if (empty($this->wordpressPath)) {

	if ($options['server'] === 'local') {
		$this->wordpressPath = '/home/nicholas/Dev/wordpress/tuauto';
	}
	if ($options['server'] === 'vm') {
		$this->wordpressPath = '/var/www/public';
	}
	if ($options['server'] === 'staging') {
		$this->wordpressPath = '/srv/www/test2.telecoms.com';
	}
	if ($options['server'] === 'beta') {
		$this->wordpressPath = '/srv/www/test1.telecoms.com';
	}
}

