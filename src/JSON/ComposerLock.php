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
				return $lockPackage;
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

	public function getPackageDependencies($package) {
		return $this->getPackageValue($package, 'require');
	}

	public function getFilteredJSON($package) {
		$data = $this->data;
		$data['packages'] = [$this->getPackageData($package)];
		return json_encode($data);
	}
}
