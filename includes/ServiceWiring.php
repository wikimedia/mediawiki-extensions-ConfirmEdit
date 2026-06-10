<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\hCaptcha;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\ConfirmEdit\Auth\LoginAttemptCounterFactory;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\HCaptchaBlocksLookup;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\HCaptchaEnterpriseHealthChecker;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\HCaptchaOutput;
use MediaWiki\Extension\ConfirmEdit\Services\CaptchaFactory;
use MediaWiki\Extension\ConfirmEdit\Services\LoadedCaptchasProvider;
use MediaWiki\Logger\LoggerFactory;
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
			),
			$services->getResourceLoader(),
			$services->getLanguageFallback(),
			$services->get( 'ConfirmEditCaptchaFactory' )
		);
	},
	'HCaptchaEnterpriseHealthChecker' => static function (
		MediaWikiServices $services
	): HCaptchaEnterpriseHealthChecker {
		return new HCaptchaEnterpriseHealthChecker(
			new ServiceOptions(
				HCaptchaEnterpriseHealthChecker::CONSTRUCTOR_OPTIONS,
				$services->getMainConfig()
			),
			LoggerFactory::getInstance( 'captcha' ),
			$services->getObjectCacheFactory()->getLocalClusterInstance(),
			$services->getMainWANObjectCache(),
			$services->getStatsFactory()
		);
	},
	'ConfirmEditLoadedCaptchasProvider' => static function ( MediaWikiServices $services ) {
		return new LoadedCaptchasProvider(
			new ServiceOptions(
				LoadedCaptchasProvider::CONSTRUCTOR_OPTIONS,
				$services->getMainConfig()
			)
		);
	},
	'ConfirmEditCaptchaFactory' => static fn ( MediaWikiServices $services ) => new CaptchaFactory(
		new ServiceOptions(
			CaptchaFactory::CONSTRUCTOR_OPTIONS,
			$services->getMainConfig()
		),
		$services->getHookContainer(),
		$services->get( 'ConfirmEditLoginAttemptCounterFactory' )
	),
	'ConfirmEditLoginAttemptCounterFactory' => static fn () => new LoginAttemptCounterFactory(),
	'ConfirmEditHCaptchaBlocksLookup' => static fn () => new HCaptchaBlocksLookup(),
];
// @codeCoverageIgnoreEnd
