<?php

namespace ComposerPatchManager;

use Composer\Installer\PackageEvent;

final class PackageUtils {
    public static function getPackageName(PackageEvent $event) {
		/** @var InstallOperation|UpdateOperation $operation */
		$operation = $event->getOperation();

		$package = method_exists($operation, 'getPackage')
			? $operation->getPackage()
			: $operation->getInitialPackage();

		return $package->getName();
    }

	public static function createCpmDir() {
		$dir = self::createSafeDir(getcwd().'/.cpm');
		file_put_contents("$dir/composer.json", '{"require": {}}');
		echo "PackageUtils: \e[36mCreated dir \e[33m$dir\e[0m".PHP_EOL;
		return $dir;
	}

	public static function getSafeDirName($dir) {
		while(is_dir($dir)) $dir .= "_";
		return $dir;
	}

	public static function createSafeDir($dir) {
		$safeDir = self::getSafeDirName($dir);
		mkdir($safeDir);
		return $safeDir;
	}

	public static function makeWindowsPathUnix($path) {
		return str_replace("\\", "/", $path);
	}
}
