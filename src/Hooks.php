<?php

namespace ComposerPatchManager;

use Composer\Installer\PackageEvent;
use ComposerPatchManager\PackageUtils;
use ComposerPatchManager\JSON\ConfigJSON;
use ComposerPatchManager\Proxy\GitProxy;

class Hooks {
    public static function postPackageInstall(PackageEvent $event) {
		$package = PackageUtils::getPackageName($event);

		$config = new ConfigJSON();
		$patches = $config->getPatchesForPackage($package);

		foreach($patches as $patch) {
			GitProxy::applyPatch($patch);
		}
	}
}
