<?php

namespace ComposerPatchManager\JSON;

use ComposerPatchManager\JSON\JSONHandler;

class ComposerLock extends JSONHandler {
	public function __construct() {
		parent::__construct(getcwd().'/composer.lock');
	}

	public function getPackageData($package) {
		foreach($this->data['packages'] as $lockPackage) {
			if(strtolower($lockPackage['name']) == strtolower($package)) {
				return $package;
			}
		}
	}

	public function getPackageValue($package, $key) {
		$data = $this->getPackageData($package);
		return empty($data) ? '' : $data[$key];
	}

	public function getPackageVersion($package) {
		return $this->getPackageValue($package, 'version');
	}

	public function getPackageType($package) {
		return $this->getPackageValue($package, 'type');
	}
}
