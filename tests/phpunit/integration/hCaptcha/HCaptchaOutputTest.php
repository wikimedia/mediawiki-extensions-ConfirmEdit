<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration\hCaptcha;

use MediaWiki\Extension\ConfirmEdit\CaptchaTriggers;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\HCaptchaOutput;
use MediaWiki\Extension\ConfirmEdit\Tests\Integration\CaptchaTestHelperTrait;
use MediaWiki\Language\Language;
use MediaWiki\Output\OutputPage;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMUtils;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\HCaptchaOutput
 */
class HCaptchaOutputTest extends MediaWikiIntegrationTestCase {
	use CaptchaTestHelperTrait;

	protected function setUp(): void {
		parent::setUp();
		$captchaTrigger = [ 'trigger' => true, 'class' => 'HCaptcha' ];
		$this->overrideConfigValues( [
			'CaptchaTriggers' => [
				CaptchaTriggers::EDIT => $captchaTrigger,
				CaptchaTriggers::CREATE => $captchaTrigger,
			],
			'HCaptchaSiteKey' => 'test-site-key',
			'HCaptchaApiUrl' => 'https://hcaptcha.example.com/api.js',
			'HCaptchaApiUrlIntegrityHash' => 'sha384-testhash',
			'HCaptchaInvisibleMode' => false,
			'HCaptchaEnterprise' => false,
			'HCaptchaSecureEnclave' => false,
		] );
		self::clearCaptchaFactoryGlobalInstances();
	}

	/** @dataProvider provideClientPrefs */
	public function testDarkModeAttribute( ?string $cookieValue, bool $expectDarkTheme ): void {
		$request = new FauxRequest();
		if ( $cookieValue !== null ) {
			$request->setCookie( 'mwclientpreferences', $cookieValue );
		}

		/** @var HCaptchaOutput $service */
		$service = $this->getServiceContainer()->get( 'HCaptchaOutput' );
		$html = $service->addHCaptchaToForm( $this->mockOutputPage( $request ), false );

		$widget = DOMCompat::querySelector( DOMUtils::parseHTML( $html ), '#h-captcha' );
		$this->assertNotNull( $widget, 'The hCaptcha widget should be present in the output' );

		if ( $expectDarkTheme ) {
			$this->assertSame( 'dark', $widget->getAttribute( 'data-theme' ) );
		} else {
			$this->assertFalse( $widget->hasAttribute( 'data-theme' ) );
		}
	}

	public static function provideClientPrefs(): iterable {
		yield 'night mode token sets dark theme' => [
			'cookieValue' => 'skin-theme-clientpref-night',
			'expectDarkTheme' => true,
		];
		yield 'night mode token among several tokens sets dark theme' => [
			'cookieValue' => 'mf-font-size-clientpref-small,skin-theme-clientpref-night',
			'expectDarkTheme' => true,
		];
		yield 'day mode token does not set theme' => [
			'cookieValue' => 'skin-theme-clientpref-day',
			'expectDarkTheme' => false,
		];
		yield 'no cookie does not set theme' => [
			'cookieValue' => null,
			'expectDarkTheme' => false,
		];
	}

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
			'Language code is represented differently by hCaptcha' => [
				'languageCode' => 'pt-br',
				'expectedUrl' => 'https://hcaptcha.example.com/api.js?hl=pt-BR',
			],
		];
	}

	private function mockOutputPage( FauxRequest $request ): OutputPage {
		$language = $this->createMock( Language::class );
		$language->method( 'getCode' )->willReturn( 'en' );

		$title = $this->createMock( Title::class );
		$title->method( 'isSpecial' )->willReturn( false );
		$title->method( 'exists' )->willReturn( true );

		$outputPage = $this->createMock( OutputPage::class );
		$outputPage->method( 'getRequest' )->willReturn( $request );
		$outputPage->method( 'getTitle' )->willReturn( $title );
		$outputPage->method( 'getActionName' )->willReturn( 'view' );
		$outputPage->method( 'getLanguage' )->willReturn( $language );
		$outputPage->method( 'msg' )
			->willReturnCallback( static fn ( $key ) => wfMessage( $key ) );
		return $outputPage;
	}
}
