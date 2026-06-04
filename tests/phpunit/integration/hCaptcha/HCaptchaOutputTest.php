<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration\hCaptcha;

use MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\HCaptchaOutput;
use MediaWiki\Extension\ConfirmEdit\Tests\Integration\CaptchaTestHelperTrait;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\HCaptchaOutput
 */
class HCaptchaOutputTest extends MediaWikiIntegrationTestCase {
	use CaptchaTestHelperTrait;

	/** @dataProvider provideGetHCaptchaApiUrl */
	public function testGetHCaptchaApiUrl( string $languageCode, string $expectedUrl ): void {
		$this->overrideConfigValue( 'HCaptchaApiUrl', 'https://hcaptcha.example.com/api.js' );

		/** @var HCaptchaOutput $service */
		$service = $this->getServiceContainer()->get( 'HCaptchaOutput' );

		$this->assertSame( $expectedUrl, $service->getHCaptchaApiUrl( $languageCode ) );
	}

	public static function provideGetHCaptchaApiUrl(): array {
		return [
			'Language code or any fallback is not supported by hCaptcha' => [
				'languageCode' => 'fss',
				'expectedUrl' => 'https://hcaptcha.example.com/api.js',
			],
			'Language code is supported by hCaptcha' => [
				'languageCode' => 'de',
				'expectedUrl' => 'https://hcaptcha.example.com/api.js?hl=de',
			],
			'Language in fallback chain is supported by hCaptcha' => [
				'languageCode' => 'abs',
				'expectedUrl' => 'https://hcaptcha.example.com/api.js?hl=id',
			],
			'First supported language in fallback chain is chosen' => [
				'languageCode' => 'oc',
				'expectedUrl' => 'https://hcaptcha.example.com/api.js?hl=ca',
			],
			'Language code with dash where fallback chain language is supported' => [
				'languageCode' => 'en-gb',
				'expectedUrl' => 'https://hcaptcha.example.com/api.js?hl=en',
			],
		];
	}
}
