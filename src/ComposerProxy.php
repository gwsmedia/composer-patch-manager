<?php

namespace ComposerPatchManager;

require_once(getcwd().'/vendor/autoload.php');

use Composer\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;

class ComposerProxy {
	private $cwd;
	private $cpmDir;
	private $application;

	public function __construct($cpmDir) {
		$this->cwd = getcwd();
		$this->cpmDir = $cpmDir;

		$this->application = new Application();
		$this->application->setAutoExit(false);
	}

	public function requirePackage($package) {
		$this->executeCommand('require', $package);
	}

	public function installPackage($package) {
		$this->executeCommand('install', $package);
	}

	public function updatePackage($package) {
		$this->executeCommand('update', $package);
	}

	public function removePackage($package) {
		$this->executeCommand('remove', $package);
	}

	private function executeCommand($command, $package = null) {
		chdir($this->cpmDir);
				
		$opts = array('command' => $command);

		if(!empty($package)) $opts['packages'] = [$package];

		$input = new ArrayInput($opts);

		echo PHP_EOL."ComposerProxy: \e[36mOutput for \e[35mcomposer ".$input->__toString()."\e[0m".PHP_EOL;
		echo "----------------------------".PHP_EOL;
		$this->application->run($input);
		echo "----------------------------".PHP_EOL.PHP_EOL;

		chdir($this->cwd);
	}
}
