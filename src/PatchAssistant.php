<?php

namespace ComposerPatchManager;

use ComposerPatchManager\PackageUtils;
use ComposerPatchManager\JSON\ConfigJSON;
use ComposerPatchManager\JSON\ComposerLock;
use ComposerPatchManager\Proxy\GitProxy;
use ComposerPatchManager\Proxy\ComposerProxy;
use Symfony\Component\Filesystem\Filesystem;

class PatchAssistant {
	private $cpmDir;
	private $configJSON;
	private $composerProxy;
	private $filesystem;

	public function __construct() {
		$this->cpmDir = PackageUtils::createCpmDir();
		$this->configJSON = new ConfigJSON();
		$this->composerLock = new ComposerLock();
		$this->composerProxy = new ComposerProxy($this->cpmDir);
		$this->filesystem = new Filesystem();
	}


	public function clean() {
		echo "PackageUtils: \e[36mDeleting dir \e[33m$this->cpmDir\e[0m".PHP_EOL;
		$this->filesystem->remove([$this->cpmDir]);
	}


	public function generatePatches() {
		$this->showWarnings();
		foreach($this->configJSON->getHackedPackages() as $package) $this->generatePatch(strtolower($package));
	}


	public function applyPatches() {
		$patches = $this->configJSON->getPatches();
		foreach($patches as $package) {
			foreach($package as $patch) {
				GitProxy::applyPatch($patch);
			}
		}

		$this->showFailedPatches();
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

		$pkgVersion = $this->composerLock->getPackageVersion($package);
		$this->composerProxy->requirePackage("$package $pkgVersion");
		$sourcePkdDir = $this->cpmDir."/vendor/$package";

		echo "PatchAssistant: \e[36mCoping altered \e[32m$package\e[0m to \e[33m{$this->cpmDir}/vendor\e[0m" . PHP_EOL;

		$alteredPkgDir = PackageUtils::createSafeDir($sourcePkdDir);
		$this->filesystem->mirror($packageDir, $alteredPkgDir);

		echo "PatchAssistant: \e[36mComparing dirs\e[0m" . PHP_EOL;

		$patchPath = 'patch/' . str_replace('/', '--', $package) . '.patch';
		if(!file_exists('patch')) mkdir('patch', 0777, true);
		GitProxy::diff($sourcePkdDir, $alteredPkgDir, $patchPath);

		$this->sanitisePatch($patchPath, $alteredPkgDir, $sourcePkdDir);

		echo "PatchAssistant: \e[36mPatchfile created at \e[32m$patchPath\e[0m" . PHP_EOL;

		$this->configJSON->data['patches'][$package][$patchPath] = $patchPath;
		$this->configJSON->save();

		echo "PatchAssistant: \e[33mcomposer-patches.json\e[36m updated\e[0m" . PHP_EOL;
		echo "PatchAssistant: \e[36mDeleting temporary packages\e[0m" . PHP_EOL;

		$this->filesystem->remove([$alteredPkgDir, $sourcePkdDir]);
	}


	// Sanitising filepaths for patch
	private function sanitisePatch($patchPath, $searchDir, $replaceDir) {
		$patch = file_get_contents($patchPath);
		$searchDir = PackageUtils::makeWindowsPathUnix($searchDir);
		$replaceDir = PackageUtils::makeWindowsPathUnix($replaceDir);

		// Replacing Windows paths with Unix paths
		$windowsCwd = str_replace("\\", "\\\\", getcwd());
		$unixCwd = PackageUtils::makeWindowsPathUnix(getcwd());
		$patch = str_replace($windowsCwd, $unixCwd, $patch);

		$patch = str_replace($searchDir, $replaceDir, $patch);

		$cpmDir = PackageUtils::makeWindowsPathUnix($this->cpmDir);
		if(strpos($cpmDir, '/') !== 0) $cpmDir = "/$cpmDir";
		$patch = str_replace($cpmDir, '', $patch);

		file_put_contents($patchPath, $patch);
	}

	
	private function showFailedPatches() {
		echo "Not done yet".PHP_EOL;
	}
}
