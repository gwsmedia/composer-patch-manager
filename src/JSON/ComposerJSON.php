<?php

namespace ComposerPatchManager\JSON;

use ComposerPatchManager\JSON\JSONHandler;

class ComposerJSON extends JSONHandler {
	const INSTALL_HOOK = "ComposerPatchManager\\Hooks::postPackageInstall";

	public function __construct() {
		parent::__construct(getcwd().'/composer.json');
	}

	public function updatePostPackageScripts() {
		$this->updatePostPackageScript('install');
		$this->updatePostPackageScript('update');
	}

	private function updatePostPackageScript($type) {
		$event = "post-package-$type";

		echo "ComposerJSON: \e[36mAdding \e[35m$event\e[36m hook\e[0m".PHP_EOL;

		if(isset($this->data['scripts'][$event])) {

			$scripts = $this->data['scripts'][$event];
			if(is_string($scripts)) $scripts = [$scripts];

		} else $scripts = [];

		if(array_search(self::INSTALL_HOOK, $scripts) === false) {
			$scripts[] = self::INSTALL_HOOK;
		}

		$this->data['scripts']["post-package-$type"] = $scripts;
	}
}
