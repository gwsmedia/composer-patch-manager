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


	public static function getPackagePath($package, $installerPath = false, $relative = false) {
		$prefix = $relative ? '' : getcwd().'/';
		if($installerPath === false) {
			return $prefix . 'vendor/' . $package;
		} else {
			return $prefix . $installerPath;
		}
	}


	public static function createCpmDir($repos, $stability) {
		$dir = self::createSafeDir(getcwd().'/.cpm');
		file_put_contents("$dir/composer.json", '{"require": {}, "repositories": '.$repos.', "minimum-stability": "'.$stability.'"}');
		echo "PackageUtils: \e[36mCreated dir \e[33m$dir\e[0m".PHP_EOL;
		return $dir;
	}

	public static function getSafeDirName($dir) {
		while(is_dir($dir)) $dir .= "_";
		return $dir;
	}

	public static function createSafeDir($dir) {
		$cwd = getcwd();
		$safeDir = self::getSafeDirName($dir);
		$pathArray = explode('/', $safeDir);

		foreach($pathArray as $pathSegment) {
			if(!is_dir($pathSegment)) mkdir($pathSegment);
			chdir($pathSegment);
		}

		chdir($cwd);
		return $safeDir;
	}

	public static function makeWindowsPathUnix($path) {
		return str_replace("\\", "/", $path);
	}

	public static function showFailedHunks($package) {
		$hunks = self::findFailedHunks(getcwd().'/vendor/'.$package);
		if(!empty($hunks)) {
			echo "PatchAssistant: \e[31mFailed patch hunks for \e[32m$package\e[31m:\e[0m".PHP_EOL;

			foreach($hunks as $hunk) {
				echo "\t\e[31m$hunk\e[0m".PHP_EOL;
			}
		}
	}

	private static function findFailedHunks($folder, $rejFiles = []) {
		if(!is_dir($folder)) return [];

		$exclude = ['.', '..'];
		$files = scandir($folder);

		foreach($files as $file) {
			$path = "$folder/$file";

			if(in_array($file, $exclude))
				continue;

			else if(is_dir($path))
				$rejFiles = self::findFailedHunks($path, $rejFiles);

			else if(preg_match('/.rej$/', $file))
				$rejFiles[] = $path;
		}

		return $rejFiles;
	}
}
