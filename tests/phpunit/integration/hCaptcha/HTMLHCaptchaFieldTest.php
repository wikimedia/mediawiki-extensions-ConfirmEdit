<?php

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration\hCaptcha;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\HTMLHCaptchaField;
use MediaWiki\Extension\ConfirmEdit\Tests\Integration\MockHCaptchaConfigTrait;
use MediaWiki\HTMLForm\HTMLForm;
use MediaWiki\Output\OutputPage;
use MediaWiki\Request\ContentSecurityPolicy;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\hCaptcha\HTMLHCaptchaField
 * @covers \MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\HCaptchaOutput
 */
class HTMLHCaptchaFieldTest extends MediaWikiIntegrationTestCase {
	use MockHCaptchaConfigTrait;

	/**
	 * @dataProvider provideOptions
	 */
	public function testRender(
		array $configOverrides,
		array $params,
		string $expectedHtml
	): void {
		$this->overrideConfigValues( $configOverrides );

		$defaultSrcs = [];
		$scriptSrcs = [];
		$styleSrcs = [];
		$modules = [];
		$jsConfigVars = [];

		$csp = $this->createMock( ContentSecurityPolicy::class );
		$csp->method( 'addDefaultSrc' )
			->willReturnCallback( static function ( $src ) use ( &$defaultSrcs ): void {
				$defaultSrcs[] = $src;
			} );
		$csp->method( 'addScriptSrc' )
			->willReturnCallback( static function ( $src ) use ( &$scriptSrcs ): void {
				$scriptSrcs[] = $src;
			} );
		$csp->method( 'addStyleSrc' )
			->willReturnCallback( static function ( $src ) use ( &$styleSrcs ): void {
				$styleSrcs[] = $src;
			} );

		$shouldSecureEnclaveModeBeEnabled = $configOverrides['HCaptchaEnterprise'] &&
			$configOverrides['HCaptchaSecureEnclave'];

		$output = $this->createMock( OutputPage::class );
		$output->method( 'getCSP' )
			->willReturn( $csp );
		$output->expects( $shouldSecureEnclaveModeBeEnabled ? $this->never() : $this->once() )
			->method( 'addHeadItem' )
			->with(
				'h-captcha',
				"<script src=\"{$configOverrides['HCaptchaApiUrl']}\" async=\"\" defer=\"\"></script>"
			);
		$output->method( 'msg' )
			->willReturnCallback( static fn ( $key ) => wfMessage( $key ) );
		$output->method( 'addModules' )
			->willReturnCallback( static function ( $module ) use ( &$modules ): void {
				$modules[] = $module;
			} );
		$output->method( 'addJsConfigVars' )
			->willReturnCallback( static function ( $key, $value ) use ( &$jsConfigVars ): void {
				if ( is_array( $key ) ) {
					$jsConfigVars = array_merge( $jsConfigVars, $key );
				} else {
					$jsConfigVars[$key] = $value;
				}
			} );

		$context = RequestContext::getMain();
		$context->setLanguage( 'qqx' );
		$context->setOutput( $output );

		$form = HTMLForm::factory( 'ooui', [], $context );
		$field = new HTMLHCaptchaField( $params + [ 'parent' => $form, 'name' => 'ignored' ] );

		$this->assertSame( 'h-captcha-response', $field->getName() );
		$this->assertSame( $expectedHtml, $field->getInputHTML( null ) );

		$this->assertSame( $configOverrides['HCaptchaCSPRules'], $defaultSrcs );
		$this->assertSame( $configOverrides['HCaptchaCSPRules'], $styleSrcs );
		$this->assertSame( $configOverrides['HCaptchaCSPRules'], $scriptSrcs );

		$this->assertSame( [ 'ext.confirmEdit.hCaptcha' ], $modules );
		$this->assertArrayEquals(
			[
				'hCaptchaApiUrl' => 'https://hcaptcha.example.com/api',
				'hCaptchaUseSecureEnclave' => $shouldSecureEnclaveModeBeEnabled,
			],
			$jsConfigVars, false, true
		);
	}

	public static function provideOptions(): iterable {
		$testApiUrl = 'https://hcaptcha.example.com/api';
		$testSiteKey = 'foo';
		$testCspRules = [
			'https://hcaptcha.example.com',
			'https://hcaptcha-2.example.com'
		];

		yield 'active mode, no prior error' => [
			[
				'HCaptchaApiUrl' => $testApiUrl,
				'HCaptchaInvisibleMode' => false,
				'HCaptchaCSPRules' => $testCspRules,
				'HCaptchaSiteKey' => $testSiteKey,
				'HCaptchaEnterprise' => false,
				'HCaptchaSecureEnclave' => false,
			],
			[],
			"<div id=\"h-captcha\" class=\"h-captcha\" data-sitekey=\"$testSiteKey\"></div>"
		];

		yield 'invisible mode, no prior error' => [
			[
				'HCaptchaApiUrl' => $testApiUrl,
				'HCaptchaInvisibleMode' => true,
				'HCaptchaCSPRules' => $testCspRules,
				'HCaptchaSiteKey' => $testSiteKey,
				'HCaptchaEnterprise' => false,
				'HCaptchaSecureEnclave' => false,
			],
			[],
			"<div id=\"h-captcha\" class=\"h-captcha\" data-sitekey=\"$testSiteKey\" data-size=\"invisible\"></div>" .
				"(hcaptcha-privacy-policy)"
		];

		yield 'active mode, prior error set' => [
			[
				'HCaptchaApiUrl' => $testApiUrl,
				'HCaptchaInvisibleMode' => false,
				'HCaptchaCSPRules' => $testCspRules,
				'HCaptchaSiteKey' => $testSiteKey,
				'HCaptchaEnterprise' => false,
				'HCaptchaSecureEnclave' => false,
			],
			[ 'error' => 'some-error' ],
			"<div id=\"h-captcha\" class=\"h-captcha mw-confirmedit-captcha-fail\" data-sitekey=\"$testSiteKey\"></div>"
		];

		yield 'invisible mode, prior error set' => [
			[
				'HCaptchaApiUrl' => $testApiUrl,
				'HCaptchaInvisibleMode' => true,
				'HCaptchaCSPRules' => $testCspRules,
				'HCaptchaSiteKey' => $testSiteKey,
				'HCaptchaEnterprise' => false,
				'HCaptchaSecureEnclave' => false,
			],
			[ 'error' => 'some-error' ],
			"<div id=\"h-captcha\" class=\"h-captcha mw-confirmedit-captcha-fail\" " .
				"data-sitekey=\"$testSiteKey\" data-size=\"invisible\"></div>(hcaptcha-privacy-policy)"
		];

		yield 'active mode, secure enclave mode enabled without enterprise mode enabled' => [
			[
				'HCaptchaApiUrl' => $testApiUrl,
				'HCaptchaInvisibleMode' => false,
				'HCaptchaCSPRules' => $testCspRules,
				'HCaptchaSiteKey' => $testSiteKey,
				'HCaptchaEnterprise' => false,
				'HCaptchaSecureEnclave' => true,
			],
			[ 'error' => 'some-error' ],
			"<div id=\"h-captcha\" class=\"h-captcha mw-confirmedit-captcha-fail\" data-sitekey=\"$testSiteKey\"></div>"
		];

		yield 'active mode, secure enclave mode enabled' => [
			[
				'HCaptchaApiUrl' => $testApiUrl,
				'HCaptchaInvisibleMode' => false,
				'HCaptchaCSPRules' => $testCspRules,
				'HCaptchaSiteKey' => $testSiteKey,
				'HCaptchaEnterprise' => true,
				'HCaptchaSecureEnclave' => true,
			],
			[ 'error' => 'some-error' ],
			'<div id="h-captcha" class="h-captcha mw-confirmedit-captcha-fail" ' .
				"data-sitekey=\"$testSiteKey\"></div>",
		];

		yield 'invisible mode, secure enclave mode enabled' => [
			[
				'HCaptchaApiUrl' => $testApiUrl,
				'HCaptchaInvisibleMode' => true,
				'HCaptchaCSPRules' => $testCspRules,
				'HCaptchaSiteKey' => $testSiteKey,
				'HCaptchaEnterprise' => true,
				'HCaptchaSecureEnclave' => true,
			],
			[ 'error' => 'some-error' ],
			'<div id="h-captcha" class="h-captcha mw-confirmedit-captcha-fail" ' .
				"data-sitekey=\"$testSiteKey\" data-size=\"invisible\"></div>" .
				'(hcaptcha-privacy-policy)',
		];
	}
}
