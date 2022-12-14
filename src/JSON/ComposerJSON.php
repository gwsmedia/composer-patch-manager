<?php

namespace ComposerPatchManager\JSON;

require_once(__DIR__.'/JSONHandler.php');

use ComposerPatchManager\JSON\JSONHandler;


class ComposerJSON extends JSONHandler {
	public function __construct() {
		parent::__construct(getcwd().'/composer.json');
	}
}
