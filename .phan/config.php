<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['directory_list'] = array_merge(
	$cfg['directory_list'],
	[
		'FancyCaptcha/',
		'hCaptcha/',
		'QuestyCaptcha/',
		'ReCaptchaNoCaptcha/',
		'SimpleCaptcha/',
	]
);

return $cfg;
