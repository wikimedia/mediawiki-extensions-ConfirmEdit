<?php
namespace MediaWiki\Extension\ConfirmEdit\Test;

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

		$output = $this->createMock( OutputPage::class );
		$output->method( 'getCSP' )
			->willReturn( $csp );
		$output->expects( $this->once() )
			->method( 'addHeadItem' )
			->with(
				'h-captcha',
				"<script src=\"{$configOverrides['HCaptchaApiUrl']}\" async defer></script>"
			);
		$output->method( 'msg' )
			->willReturnCallback( static fn ( $key ) => wfMessage( $key ) );

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
				'HCaptchaPassiveMode' => false,
				'HCaptchaCSPRules' => $testCspRules,
				'HCaptchaSiteKey' => $testSiteKey,
			],
			[],
			"<div class=\"h-captcha\" data-sitekey=\"$testSiteKey\"></div>"
		];

		yield 'passive mode, no prior error' => [
			[
				'HCaptchaApiUrl' => $testApiUrl,
				'HCaptchaPassiveMode' => true,
				'HCaptchaCSPRules' => $testCspRules,
				'HCaptchaSiteKey' => $testSiteKey,
			],
			[],
			"<div class=\"h-captcha\" data-sitekey=\"$testSiteKey\"></div>(hcaptcha-privacy-policy)"
		];

		yield 'active mode, prior error set' => [
			[
				'HCaptchaApiUrl' => $testApiUrl,
				'HCaptchaPassiveMode' => false,
				'HCaptchaCSPRules' => $testCspRules,
				'HCaptchaSiteKey' => $testSiteKey,
			],
			[ 'error' => 'some-error' ],
			"<div class=\"h-captcha mw-confirmedit-captcha-fail\" data-sitekey=\"$testSiteKey\"></div>"
		];

		yield 'passive mode, prior error set' => [
			[
				'HCaptchaApiUrl' => $testApiUrl,
				'HCaptchaPassiveMode' => true,
				'HCaptchaCSPRules' => $testCspRules,
				'HCaptchaSiteKey' => $testSiteKey,
			],
			[ 'error' => 'some-error' ],
			"<div class=\"h-captcha mw-confirmedit-captcha-fail\" data-sitekey=\"$testSiteKey\"></div>" .
			"(hcaptcha-privacy-policy)"
		];
	}
}
