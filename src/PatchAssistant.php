<?php

namespace ComposerPatchManager;

require(getcwd() . '/vendor/autoload.php');
require(getcwd() . '/src/PackageUtils.php');
require(getcwd() . '/src/ComposerProxy.php');

use ComposerPatchManager\PackageUtils;
use ComposerPatchManager\ComposerProxy;
use Symfony\Component\Filesystem\Filesystem;

class PatchAssistant {
	private $packages;
	private $composerProxy;
	private $filesystem;

	public function __construct() {
		$this->packages = $this->getHackedPackages();
		$this->composerProxy = new ComposerProxy();
		$this->filesystem = new Filesystem();
	}


	public function generatePatches() {
		foreach($this->packages as $package) $this->generatePatch($package);
	}


	private function getHackedPackages() {
		$hacksJSONPath = getcwd() . '/composer-hacks.json';

		if(!file_exists($hacksJSONPath)) {
			echo "PatchAssistant: \e[31mcould not find 'composer-hacks.json'. Please check the README.\e[0m" . PHP_EOL;
			die();
		}

		$json = file_get_contents($hacksJSONPath);
		$jsonData = json_decode($json, true);

		if(empty($jsonData)) {
			echo "PatchAssistant: \e[31m'composer-hacks.json' is not a valid JSON.\e[0m" . PHP_EOL;
		}

		return isset($jsonData['packages']) && is_array($jsonData['packages']) ? $jsonData['packages'] : [];
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
