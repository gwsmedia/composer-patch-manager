<?php

namespace ComposerPatchManager\JSON;

use ComposerPatchManager\JSON\JSONHandler;

class ConfigJSON extends JSONHandler {
	public function __construct() {
		parent::__construct(getcwd().'/composer-patches.json');
	}

	public function getHackedPackages() {
		if(!isset($this->data['packages']) || !is_array($this->data['packages'])) {
		  	die("ConfigJSON: \e[31m'packages' not currectly specified in 'composer-patches.json'.\e[0m".PHP_EOL);
		}
		
		return $this->data['packages'];
	}

}
