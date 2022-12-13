<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['directory_list'] = array_merge(
	$cfg['directory_list'],
	[
		'FancyCaptcha/',
		'hCaptcha/',
		'MathCaptcha/',
		'QuestyCaptcha/',
		'ReCaptchaNoCaptcha/',
		'SimpleCaptcha/',
		'../../extensions/Math',
	]
);

$cfg['exclude_analysis_directory_list'] = array_merge(
	$cfg['exclude_analysis_directory_list'],
	[
		'../../extensions/Math',
	]
);

return $cfg;
