<?php

namespace ComposerPatchManager\JSON;

use ComposerPatchManager\JSON\JSONHandler;

class ComposerJSON extends JSONHandler {
	public function __construct() {
		parent::__construct(getcwd().'/composer.json');
	}
}
