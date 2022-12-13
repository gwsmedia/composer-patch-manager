<?php

namespace ComposerPatchManager;

use Composer\Installer\PackageEvent;

class PackageUtils {
    public static function getPackageName(PackageEvent $event) {
		/** @var InstallOperation|UpdateOperation $operation */
		$operation = $event->getOperation();

		$package = method_exists($operation, 'getPackage')
			? $operation->getPackage()
			: $operation->getInitialPackage();

		return $package->getName();
    }

	public static function createTempDir($packageDir) {
		while(is_dir($packageDir)) $packageDir .= "_";
		mkdir($packageDir);
		return $packageDir;
	}
}
