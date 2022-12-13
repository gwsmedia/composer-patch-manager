<?php

namespace ComposerPatchManager;

require getcwd().'/vendor/autoload.php';

use Composer\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;

class ComposerProxy {
	private $application;

	public function __construct() {
		putenv('COMPOSER_HOME=' . __DIR__ . '/vendor/bin/composer');

		$this->application = new Application();
		$this->application->setAutoExit(false);
	}

	public function updatePackage($package) {
		$input = new ArrayInput(array('command' => 'update', 'packages' => [$package]));
		$this->application->run($input);
	}
}
