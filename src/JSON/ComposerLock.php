<?php

namespace ComposerPatchManager\JSON;

use ComposerPatchManager\JSON\JSONHandler;

class ComposerLock extends JSONHandler {
	public function __construct() {
		parent::__construct(getcwd().'/composer.lock');
	}

	public function getPackageVersion($targetPackage) {
		foreach($this->data['packages'] as $package) {
			if($package['name'] == $targetPackage) {
				return $package['version'];
			}
		}
	}
}