<?php

use MediaWiki\Config\HashConfig;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\ConfirmEdit\CaptchaTriggers;
use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
use MediaWiki\Request\WebRequest;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use Wikimedia\ScopedCallback;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha
 */
class CaptchaTest extends MediaWikiIntegrationTestCase {

	/** @var ScopedCallback[] */
	private $hold = [];

	public function tearDown(): void {
		// Destroy any ScopedCallbacks being held
		$this->hold = [];
		parent::tearDown();
	}

	/**
	 * @dataProvider provideSimpleTriggersCaptcha
	 */
	public function testTriggersCaptcha( $action, $expectedResult ) {
		$captcha = new SimpleCaptcha();
		$this->overrideConfigValue( 'CaptchaTriggers', [
			$action => $expectedResult,
		] );
		$this->assertEquals( $expectedResult, $captcha->triggersCaptcha( $action ) );
	}

	public static function provideSimpleTriggersCaptcha() {
		$data = [];
		$captchaTriggers = new ReflectionClass( CaptchaTriggers::class );
		$constants = $captchaTriggers->getConstants();
		foreach ( $constants as $const ) {
			$data[] = [ $const, true ];
			$data[] = [ $const, false ];
		}
		return $data;
	}

	/**
	 * @dataProvider provideNamespaceOverwrites
	 */
	public function testNamespaceTriggersOverwrite( $trigger, $expected ) {
		$captcha = new SimpleCaptcha();
		$this->overrideConfigValues( [
			'CaptchaTriggers' => [
				$trigger => !$expected,
			],
			'CaptchaTriggersOnNamespace' => [
				0 => [
					$trigger => $expected,
				],
			],
		] );
		$title = Title::newFromText( 'Main' );
		$this->assertEquals( $expected, $captcha->triggersCaptcha( $trigger, $title ) );
	}

	public static function provideNamespaceOverwrites() {
		return [
			[ 'edit', true ],
			[ 'edit', false ],
		];
	}

	private function setCaptchaTriggersAttribute( $trigger, $value ) {
		// Avoid clobbering captcha triggers registered by other extensions
		$this->overrideConfigValue( 'CaptchaTriggers', $GLOBALS['wgCaptchaTriggers'] );

		$this->hold[] = ExtensionRegistry::getInstance()->setAttributeForTest(
			'CaptchaTriggers', [ $trigger => $value ]
		);
	}

	/**
	 * @dataProvider provideAttributeSet
	 */
	public function testCaptchaTriggersAttributeSetTrue( $trigger, $value ) {
		$this->setCaptchaTriggersAttribute( $trigger, $value );
		$captcha = new SimpleCaptcha();
		$this->assertEquals( $value, $captcha->triggersCaptcha( $trigger ) );
	}

	public static function provideAttributeSet() {
		return [
			[ 'test', true ],
			[ 'test', false ],
		];
	}

	/**
	 * @dataProvider provideAttributeOverwritten
	 */
	public function testCaptchaTriggersAttributeGetsOverwritten( $trigger, $expected ) {
		$this->overrideConfigValue( 'CaptchaTriggers', [ $trigger => $expected ] );
		$this->setCaptchaTriggersAttribute( $trigger, !$expected );
		$captcha = new SimpleCaptcha();
		$this->assertEquals( $expected, $captcha->triggersCaptcha( $trigger ) );
	}

	public static function provideAttributeOverwritten() {
		return [
			[ 'edit', true ],
			[ 'edit', false ],
		];
	}

	/**
	 * @dataProvider provideCanSkipCaptchaUserright
	 */
	public function testCanSkipCaptchaUserright( $userIsAllowed, $expected ) {
		$testObject = new SimpleCaptcha();
		$user = $this->createMock( User::class );
		$user->method( 'isAllowed' )->willReturn( $userIsAllowed );

		$actual = $testObject->canSkipCaptcha( $user, RequestContext::getMain()->getConfig() );

		$this->assertEquals( $expected, $actual );
	}

	public static function provideCanSkipCaptchaUserright() {
		return [
			[ true, true ],
			[ false, false ]
		];
	}

	/**
	 * @dataProvider provideCanSkipCaptchaMailconfirmed
	 */
	public function testCanSkipCaptchaMailconfirmed( $allowUserConfirmEmail,
		$userIsMailConfirmed, $expected ) {
		$testObject = new SimpleCaptcha();
		$user = $this->createMock( User::class );
		$user->method( 'isEmailConfirmed' )->willReturn( $userIsMailConfirmed );
		$config = new HashConfig( [ 'AllowConfirmedEmail' => $allowUserConfirmEmail ] );

		$actual = $testObject->canSkipCaptcha( $user, $config );

		$this->assertEquals( $expected, $actual );
	}

	public static function provideCanSkipCaptchaMailconfirmed() {
		return [
			[ false, false, false ],
			[ false, true, false ],
			[ true, false, false ],
			[ true, true, true ],
		];
	}

	/**
	 * @dataProvider provideCanSkipCaptchaIPWhitelisted
	 */
	public function testCanSkipCaptchaIPWhitelisted( $requestIP, $IPWhitelist, $expected ) {
		$testObject = new SimpleCaptcha();
		$config = new HashConfig( [ 'AllowConfirmedEmail' => false ] );
		$request = $this->createMock( WebRequest::class );
		$request->method( 'getIP' )->willReturn( $requestIP );

		$this->setMwGlobals( [
			'wgRequest' => $request,
		] );
		$this->overrideConfigValue( 'CaptchaWhitelistIP', $IPWhitelist );

		$actual = $testObject->canSkipCaptcha( RequestContext::getMain()->getUser(), $config );

		$this->assertEquals( $expected, $actual );
	}

	public static function provideCanSkipCaptchaIPWhitelisted() {
		return ( [
			[ '127.0.0.1', [ '127.0.0.1', '127.0.0.2' ], true ],
			[ '127.0.0.1', [], false ]
		]
		);
	}

	public function testTriggersCaptchaReturnsEarlyIfCaptchaSolved() {
		$this->overrideConfigValue( 'CaptchaTriggers', [
			'edit' => true,
		] );
		$testObject = new SimpleCaptcha();
		/** @var SimpleCaptcha|TestingAccessWrapper $wrapper */
		$wrapper = TestingAccessWrapper::newFromObject( $testObject );
		$wrapper->captchaSolved = true;
		$this->assertFalse( $wrapper->triggersCaptcha( 'edit' ), 'CAPTCHA is not triggered if already solved' );
	}

	public function testForceShowCaptcha() {
		$this->overrideConfigValue( 'CaptchaTriggers', [
			'edit' => false,
		] );
		$testObject = new SimpleCaptcha();
		/** @var SimpleCaptcha|TestingAccessWrapper $wrapper */
		$wrapper = TestingAccessWrapper::newFromObject( $testObject );
		$this->assertFalse(
			$wrapper->triggersCaptcha( 'edit' ), 'CAPTCHA is not triggered by edit action in this configuration'
		);
		$wrapper->setForceShowCaptcha( true );
		$this->assertTrue( $wrapper->triggersCaptcha( 'edit' ), 'Force showing a CAPTCHA if flag is set' );
	}
}
