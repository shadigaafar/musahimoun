<?php

return (new PhpCsFixer\Config())
	->setRules([
		'@wordpress' => true,       // enable the WordPress coding standard
		'line_ending' => true,  // ensure LF endings
	])
	->setFinder(
		PhpCsFixer\Finder::create()
			->in(__DIR__ . '/') // adjust this to your PHP code folders
	);
