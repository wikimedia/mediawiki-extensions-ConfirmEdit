<?php

namespace MediaWiki\Extension\ConfirmEdit\hCaptcha;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\HCaptchaOutput;
use MediaWiki\MediaWikiServices;

// PHP unit does not understand code coverage for this file
// as the @covers annotation cannot cover a specific file
// This is fully tested in ServiceWiringTest.php
// @codeCoverageIgnoreStart

return [
	'HCaptchaOutput' => static function (
		MediaWikiServices $services
	): HCaptchaOutput {
		return new HCaptchaOutput(
			new ServiceOptions(
				HCaptchaOutput::CONSTRUCTOR_OPTIONS,
				$services->getMainConfig()
			)
		);
	},
];
// @codeCoverageIgnoreEnd
