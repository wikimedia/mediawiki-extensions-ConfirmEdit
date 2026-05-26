<?php

declare( strict_types=1 );

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['directory_list'] = array_merge(
	$cfg['directory_list'],
	[
		'../../extensions/AbuseFilter',
		'../../extensions/GlobalBlocking',
		'../../extensions/MobileFrontend',
		'../../extensions/VisualEditor',
	]
);

$cfg['exclude_analysis_directory_list'] = array_merge(
	$cfg['exclude_analysis_directory_list'],
	[
		'../../extensions/AbuseFilter',
		'../../extensions/GlobalBlocking',
		'../../extensions/MobileFrontend',
		'../../extensions/VisualEditor',
	]
);

$cfg['exclude_file_list'] = array_merge(
	$cfg['exclude_file_list'],
	[
		'../../extensions/VisualEditor/.phan/stubs/MobileContext.php'
	]
);

return $cfg;
