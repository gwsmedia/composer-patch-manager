<?php

namespace ComposerPatchManager\JSON;

use ComposerPatchManager\JSON\JSONHandler;

class ComposerJSON extends JSONHandler {
	const INSTALL_HOOK = "ComposerPatchManager\\Hooks::postPackageInstall";

	public static function create($dir, $require = '{}', $repos = '{}', $stability = '', $return = true) {
		file_put_contents($dir.'/composer.json', '{"require": '.$require.', "repositories": '.$repos.', "minimum-stability": "'.$stability.'"}');
		if($return) return new ComposerJSON($dir);
	}


	public function __construct($dir = null) {
		if(empty($dir)) $dir = getcwd();
		parent::__construct($dir, 'composer.json');
	}


	public function reset() {
		self::create($this->dir, '{}', $this->getRepositories(true), $this->getMinStability(), false);
		$this->refresh();
	}


	public function getRepositories($encode = false) {
		if(isset($this->data['repositories'])) {
			return $encode ? json_encode($this->data['repositories']) : $this->data['repositories'];
		}

		return $encode ? '[]' : [];
	}


	public function getMinStability() {
		return isset($this->data['minimum-stability']) ? $this->data['minimum-stability'] : 'stable';
	}


	public function getInstallerPath($package, $type) {
		if(empty($this->data['extra']['installer-paths'])) {
			return false;
		}

		$typeString = "type:$type";
		$paths = $this->data['extra']['installer-paths'];

		foreach($paths as $path => $mappings) {
			if(is_string($mappings)) $mappings = [$mappings];
			if(in_array($typeString, $mappings)) {
				$name = explode('/', $package)[1];
				return str_replace('{$name}', $name, $path);
			}
		}

		return false;
	}


	public function setInstallerPath($path, $package) {
		$this->data['require']['composer/installers'] = '*';
		$this->data['config']['allow-plugins']['composer/installers'] = true;
		$this->data['extra']['installer-paths'][$path] = $package;
	}


	public function setPackageOverrides($obj) {
		$this->data['replace'] = $obj;
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
