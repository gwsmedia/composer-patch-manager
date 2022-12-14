<?php

namespace ComposerPatchManager;

require_once(__DIR__.'/PackageUtils.php');
require_once(__DIR__.'/ComposerProxy.php');
require_once(__DIR__.'/JSON/ConfigJSON.php');
require_once(__DIR__.'/JSON/ComposerJSON.php');

use ComposerPatchManager\PackageUtils;
use ComposerPatchManager\ComposerProxy;
use ComposerPatchManager\JSON\ConfigJSON;
use ComposerPatchManager\JSON\ComposerJSON;
use Symfony\Component\Filesystem\Filesystem;

class PatchAssistant {
	private $configJSON;
	private $composerJSON;
	private $composerProxy;
	private $filesystem;

	public function __construct() {
		$this->configJSON = new ConfigJSON();
		$this->composerJSON = new ComposerJSON();
		$this->composerProxy = new ComposerProxy();
		$this->filesystem = new Filesystem();
	}


	public function generatePatches() {
		$this->showWarnings();
		foreach($this->configJSON->getHackedPackages() as $package) $this->generatePatch($package);
	}


	private function showWarnings() {
		if(in_array('exec', explode(',', ini_get('disable_functions')))) {
			die("PatchAssistant: \e[31mPHP's exec() is disabled. This is necessary to use generate the patch using 'git diff'. Either enable the function or generate the patches on a local copy of the site on which you have control of php.ini.\e[0m");
		}
	}


	private function generatePatch($package) {
		echo "PatchAssistant: \e[36mProcessing package \e[32m$package\e[0m." . PHP_EOL;

		$packageDir = getcwd() . '/vendor/' . $package;

		if(!file_exists($packageDir)) {
			echo "PatchAssistant: \e[31mcould not find package $package\e[0m.";
			return;
		}

		echo "PatchAssistant: \e[36mComparing package with unaltered source...\e[0m." . PHP_EOL . PHP_EOL;

		$tempDir = PackageUtils::createTempDir($packageDir);
		rename($packageDir, $tempDir);

		$this->composerProxy->updatePackage($package);

		$patchPath = 'patch/' . str_replace('/', '--', $package) . '.patch';
		if(!file_exists('patch')) mkdir('patch', 0777, true);
		exec("git diff --no-index --output $patchPath $packageDir $tempDir");

		$this->sanitisePatch($patchPath, $tempDir, $packageDir);

		echo PHP_EOL . "PatchAssistant: \e[36mPatchfile created at \e[32m$patchPath\e[0m." . PHP_EOL;

		$this->filesystem->remove([$packageDir]);
		rename($tempDir, $packageDir);
	}


	private function sanitisePatch($patchPath, $tempDir, $packageDir) {
		$tempDir = substr($tempDir, 1).'/';
		$packageDir = substr($packageDir, 1).'/';
		$cwd = substr(getcwd(), 1).'/';

		$patch = file_get_contents($patchPath);
		$patch = str_replace($tempDir, $packageDir, $patch);
		$patch = str_replace($cwd, '', $patch);
		file_put_contents($patchPath, $patch);
	}
}
