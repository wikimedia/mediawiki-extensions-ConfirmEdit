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

/**
 * Tests for HCaptchaOutput::addGradeCBootstrap() and shouldEmitGradeCBootstrap().
 *
 * @covers \MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\HCaptchaOutput
 * @group Database
 */
class HCaptchaOutputGradeCBootstrapTest extends MediaWikiIntegrationTestCase {
	use CaptchaTestHelperTrait;

	private const TEST_SITE_KEY = 'test-site-key-abc';
	private const TEST_API_URL = 'https://js.hcaptcha.com/1/secure-api.js';
	private const TEST_INTEGRITY_HASH = 'sha384-testhash';
	private const HEAD_ITEM_KEY = 'confirmedit-hcaptcha-gradec';

	protected function setUp(): void {
		parent::setUp();
		$captchaTrigger = [ 'trigger' => true, 'class' => 'HCaptcha', 'config' => [] ];
		$this->overrideConfigValue( 'CaptchaTriggers', [
			CaptchaTriggers::EDIT => $captchaTrigger,
			CaptchaTriggers::CREATE => $captchaTrigger,
			CaptchaTriggers::CREATE_ACCOUNT => $captchaTrigger,
			CaptchaTriggers::LOGIN_ATTEMPT => $captchaTrigger,
		] );
		self::clearCaptchaFactoryGlobalInstances();
	}

	private function secureEnclaveConfig( bool $enabled ): array {
		return [
			'HCaptchaSiteKey' => self::TEST_SITE_KEY,
			'HCaptchaApiUrl' => self::TEST_API_URL,
			'HCaptchaApiUrlIntegrityHash' => self::TEST_INTEGRITY_HASH,
			'HCaptchaInvisibleMode' => false,
			'HCaptchaEnterprise' => $enabled,
			'HCaptchaSecureEnclave' => $enabled,
		];
	}

	private function mockOutputPage(
		Title $title,
		string $action,
		array $jsConfigVars,
		array &$headItems
	): OutputPage {
		$mockRequest = new FauxRequest();

		$mockLanguage = $this->createMock( Language::class );
		$mockLanguage->method( 'getCode' )->willReturn( 'en' );

		$outputPage = $this->createMock( OutputPage::class );
		$outputPage->method( 'getTitle' )->willReturn( $title );
		$outputPage->method( 'getRequest' )->willReturn( $mockRequest );
		$outputPage->method( 'getActionName' )->willReturn( $action );
		$outputPage->method( 'getLanguage' )->willReturn( $mockLanguage );
		$outputPage->method( 'getJsConfigVars' )->willReturn( $jsConfigVars );
		$outputPage->method( 'msg' )
			->willReturnCallback( static fn ( $key ) => wfMessage( $key ) );
		$outputPage->method( 'addHeadItem' )
			->willReturnCallback( static function ( string $key, string $value ) use ( &$headItems ): void {
				$headItems[$key] = $value;
			} );
		return $outputPage;
	}

	private function createAccountTitle(): Title {
		$title = $this->createMock( Title::class );
		$title->method( 'isSpecial' )
			->willReturnCallback( static fn ( string $name ) => $name === 'CreateAccount' );
		$title->method( 'exists' )->willReturn( true );
		return $title;
	}

	private function regularPageTitle(): Title {
		$title = $this->createMock( Title::class );
		$title->method( 'isSpecial' )->willReturn( false );
		$title->method( 'exists' )->willReturn( true );
		return $title;
	}

	private function getService(): HCaptchaOutput {
		return $this->getServiceContainer()->get( 'HCaptchaOutput' );
	}

	/**
	 * Set config, run addHCaptchaToForm, return the head items collected.
	 */
	private function runWith(
		bool $secureEnclave,
		Title $title,
		string $action = 'view',
		array $jsConfigVars = []
	): array {
		$this->overrideConfigValues( $this->secureEnclaveConfig( $secureEnclave ) );
		$headItems = [];
		$outputPage = $this->mockOutputPage( $title, $action, $jsConfigVars, $headItems );
		$this->getService()->addHCaptchaToForm( $outputPage, false );
		return $headItems;
	}

	private function extractPayload( string $html ): array {
		$this->assertSame( 1, preg_match(
			'/window\.__confirmEditHCaptchaGradeC=(\{.*?\});window\.NORLQ/s',
			$html,
			$matches
		), 'Payload pattern should be present in bootstrap HTML' );
		$payload = json_decode( $matches[1], true );
		$this->assertIsArray( $payload );
		return $payload;
	}

	public function testEmitsBootstrapOnCreateAccount(): void {
		$headItems = $this->runWith( true, $this->createAccountTitle() );

		$this->assertArrayHasKey( self::HEAD_ITEM_KEY, $headItems );
		$html = $headItems[self::HEAD_ITEM_KEY];
		$this->assertStringContainsString( '__confirmEditHCaptchaGradeC', $html );
		$this->assertStringContainsString( 'NORLQ', $html );
		$this->assertStringContainsString( 'load.php?', $html );
		$this->assertStringContainsString( 'modules=ext.confirmEdit.hCaptcha.gradeC', $html );
		$this->assertStringContainsString( 'only=scripts', $html );
		$this->assertStringContainsString( 'raw=1', $html );
	}

	public function testEmitsBootstrapOnUserlogin(): void {
		$title = $this->createMock( Title::class );
		$title->method( 'isSpecial' )
			->willReturnCallback( static fn ( string $name ) => $name === 'Userlogin' );
		$title->method( 'exists' )->willReturn( true );

		$headItems = $this->runWith( true, $title );

		$this->assertArrayHasKey( self::HEAD_ITEM_KEY, $headItems );
		$payload = $this->extractPayload( $headItems[self::HEAD_ITEM_KEY] );
		$this->assertSame( 'Userlogin', $payload['config']['wgCanonicalSpecialPageName'] );
	}

	public static function provideEmittingActions(): iterable {
		yield 'edit' => [ 'edit' ];
		yield 'submit' => [ 'submit' ];
	}

	/**
	 * @dataProvider provideEmittingActions
	 */
	public function testEmitsBootstrapOnAction( string $action ): void {
		$headItems = $this->runWith( true, $this->regularPageTitle(), $action );
		$this->assertArrayHasKey( self::HEAD_ITEM_KEY, $headItems );
	}

	public function testSkipsBootstrapOnUnrelatedPage(): void {
		$headItems = $this->runWith( true, $this->regularPageTitle(), 'view' );
		$this->assertArrayNotHasKey( self::HEAD_ITEM_KEY, $headItems );
	}

	public function testSkipsBootstrapWhenSecureEnclaveDisabled(): void {
		$headItems = $this->runWith( false, $this->createAccountTitle() );
		$this->assertArrayNotHasKey( self::HEAD_ITEM_KEY, $headItems );
	}

	public function testPayloadIncludesExpectedKeys(): void {
		$headItems = $this->runWith( true, $this->createAccountTitle() );
		$payload = $this->extractPayload( $headItems[self::HEAD_ITEM_KEY] );

		$this->assertArrayHasKey( 'config', $payload );
		$this->assertArrayHasKey( 'messages', $payload );
		$this->assertArrayHasKey( 'configModule', $payload );

		$config = $payload['config'];
		$this->assertSame( 'CreateAccount', $config['wgCanonicalSpecialPageName'] );
		$this->assertArrayHasKey( 'wgAction', $config );
		$this->assertSame( self::TEST_SITE_KEY, $config['wgConfirmEditHCaptchaSiteKey'] );

		$configModule = $payload['configModule'];
		$this->assertSame( self::TEST_API_URL, $configModule['HCaptchaApiUrl'] );
		$this->assertSame( self::TEST_INTEGRITY_HASH, $configModule['HCaptchaApiUrlIntegrityHash'] );
	}

	public function testPayloadEscapesSpecialChars(): void {
		// Inject < > & via a config value that ends up in the JSON payload.
		// FormatJson::UTF8_OK must escape them so they can't break out of <script>.
		$config = $this->secureEnclaveConfig( true );
		$config['HCaptchaApiUrlIntegrityHash'] = '</script><script>alert(1)</script>';
		$this->overrideConfigValues( $config );

		$headItems = [];
		$outputPage = $this->mockOutputPage( $this->createAccountTitle(), 'view', [], $headItems );
		$this->getService()->addHCaptchaToForm( $outputPage, false );

		$html = $headItems[self::HEAD_ITEM_KEY];
		$firstScriptClose = strpos( $html, '</script>' );
		$this->assertNotFalse( $firstScriptClose );
		$payloadRegion = substr( $html, strlen( '<script>' ), $firstScriptClose - strlen( '<script>' ) );

		$this->assertStringNotContainsString( '<', $payloadRegion );
		$this->assertStringNotContainsString( '>', $payloadRegion );
		$this->assertStringContainsString( '\\u003C', $payloadRegion );
	}

	public function testThreadsTriggerFormSubmission(): void {
		$headItems = $this->runWith(
			true,
			$this->createAccountTitle(),
			'view',
			[ 'wgHCaptchaTriggerFormSubmission' => true ]
		);
		$payload = $this->extractPayload( $headItems[self::HEAD_ITEM_KEY] );
		$this->assertTrue( $payload['config']['wgHCaptchaTriggerFormSubmission'] );
	}
}
