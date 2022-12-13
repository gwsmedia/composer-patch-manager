<?php

namespace ComposerPatchManager;

use Composer\Installer\PackageEvent;
use ComposerPatchManager\PackageUtils;

class Hooks {
    public static function postPackageInstall(PackageEvent $event) {
		var_dump(PackageUtils::getPackageName($event));
	}
}
