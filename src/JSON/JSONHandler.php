<?php

namespace ComposerPatchManager\JSON;

class JSONHandler {
	private $className;
	public $filepath;
	public $data;

	public function __construct($filepath) {
		$this->filepath = $filepath;
		$this->className = (new \ReflectionClass($this))->getShortName();

		if(!file_exists($this->filepath)) {
			die("{$this->className}: \e[31mcould not find '{$this->filepath}'. Please check the README.\e[0m" . PHP_EOL);
		}

		$json = file_get_contents($this->filepath);
		$this->data = json_decode($json, true);

		if(json_last_error() !== JSON_ERROR_NONE) {
			echo "{$this->className}: \e[31mError parsing {$this->filepath}\e[0m" . PHP_EOL;
			die("Reason: \e[31m".json_last_error_msg() . "\e[0m" . PHP_EOL);
		}
	}

	public function traverseForValue($keys) {
		$subtree = $this->data;
		foreach($keys as $key) {
			if(isset($subtree[$key])) {
				$subtree = $subtree[$key];
			} else {
				return false;
			}
		}
		return $subtree;
	}

	public function save() {
		file_put_contents($this->filepath, json_encode($this->data, JSON_PRETTY_PRINT));
	}
}
