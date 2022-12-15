<?php

namespace ComposerPatchManager;

use ComposerPatchManager\PackageUtils;
use ComposerPatchManager\ComposerProxy;
use ComposerPatchManager\JSON\ConfigJSON;
use ComposerPatchManager\JSON\ComposerJSON;
use Symfony\Component\Filesystem\Filesystem;

class PatchAssistant {
	private $cpmDir;
	private $configJSON;
	private $composerJSON;
	private $composerProxy;
	private $filesystem;

	public function __construct() {
		$this->cpmDir = PackageUtils::createCpmDir();
		$this->configJSON = new ConfigJSON();
		$this->composerJSON = new ComposerJSON();
		$this->composerProxy = new ComposerProxy($this->cpmDir);
		$this->filesystem = new Filesystem();
	}


	public function clean() {
		echo "PackageUtils: \e[36mDeleting dir \e[33m$this->cpmDir\e[0m".PHP_EOL;
		$this->filesystem->remove([$this->cpmDir]);
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
		echo "PatchAssistant: \e[36mProcessing package \e[32m$package\e[0m" . PHP_EOL;

		$packageDir = getcwd() . '/vendor/' . $package;

		if(!file_exists($packageDir)) {
			echo "PatchAssistant: \e[31mcould not find $packageDir\e[0m".PHP_EOL;
			return;
		}

		echo "PatchAssistant: \e[36mComparing package with unaltered source.\e[0m" . PHP_EOL;
		echo "PatchAssistant: \e[36mDownloading fresh \e[32m$package\e[0m to \e[33m{$this->cpmDir}/vendor\e[0m" . PHP_EOL;

		$pkgVersion = $this->composerJSON->traverseForValue(['require', $package]);
		$this->composerProxy->requirePackage("$package $pkgVersion");
		$sourcePkdDir = $this->cpmDir."/vendor/$package";

		echo "PatchAssistant: \e[36mCoping altered \e[32m$package\e[0m to \e[33m{$this->cpmDir}/vendor\e[0m" . PHP_EOL;

		$alteredPkgDir = PackageUtils::createSafeDir($sourcePkdDir);
		$this->filesystem->mirror($packageDir, $alteredPkgDir);

		echo "PatchAssistant: \e[36mComparing dirs\e[0m" . PHP_EOL;

		$patchPath = 'patch/' . str_replace('/', '--', $package) . '.patch';
		if(!file_exists('patch')) mkdir('patch', 0777, true);
		exec("git diff --no-index --output \"$patchPath\" \"$sourcePkdDir\" \"$alteredPkgDir\"");

		$this->sanitisePatch($patchPath, $alteredPkgDir, $sourcePkdDir);

		echo "PatchAssistant: \e[36mPatchfile created at \e[32m$patchPath\e[0m" . PHP_EOL;

		$this->configJSON->data['patches'][$package][] = $patchPath;
		$this->configJSON->save();

		echo "PatchAssistant: \e[33mcomposer-patches.json\e[36m updated\e[0m" . PHP_EOL;
		echo "PatchAssistant: \e[36mDeleting temporary packages\e[0m" . PHP_EOL;

		$this->filesystem->remove([$alteredPkgDir, $sourcePkdDir]);
	}


	private function sanitisePatch($patchPath, $searchDir, $replaceDir) {
		$searchDir = substr($searchDir, 1).'/';
		$replaceDir = substr($replaceDir, 1).'/';
		$cpmDir = substr($this->cpmDir, 1).'/';

		$patch = file_get_contents($patchPath);
		$patch = str_replace($searchDir, $replaceDir, $patch);
		$patch = str_replace($cpmDir, '', $patch);
		file_put_contents($patchPath, $patch);
	}
}
