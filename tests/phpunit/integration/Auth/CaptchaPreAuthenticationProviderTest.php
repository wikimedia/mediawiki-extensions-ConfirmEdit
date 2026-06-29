<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration\Auth;

use MediaWiki\Auth\AuthenticationResponse;
use MediaWiki\Auth\AuthManager;
use MediaWiki\Auth\UsernameAuthenticationRequest;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\ConfirmEdit\Auth\CaptchaAuthenticationRequest;
use MediaWiki\Extension\ConfirmEdit\Auth\CaptchaPreAuthenticationProvider;
use MediaWiki\Extension\ConfirmEdit\Auth\LoginAttemptCounter;
use MediaWiki\Extension\ConfirmEdit\Auth\LoginAttemptCounterFactory;
use MediaWiki\Extension\ConfirmEdit\CaptchaTriggers;
use MediaWiki\Extension\ConfirmEdit\Services\CaptchaFactory;
use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
use MediaWiki\Extension\ConfirmEdit\Store\CaptchaHashStore;
use MediaWiki\Extension\ConfirmEdit\Store\CaptchaStore;
use MediaWiki\Extension\ConfirmEdit\Tests\Integration\CaptchaTestHelperTrait;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Tests\Unit\Auth\AuthenticationProviderTestTrait;
use MediaWiki\User\User;
use MediaWikiIntegrationTestCase;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\Auth\CaptchaPreAuthenticationProvider
 * @covers \MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha
 * @group Database
 */
class CaptchaPreAuthenticationProviderTest extends MediaWikiIntegrationTestCase {
	use AuthenticationProviderTestTrait;
	use CaptchaTestHelperTrait;

	public function setUp(): void {
		parent::setUp();

		// Clear any handlers of the ConfirmEditTriggersCaptcha hook for this test, as in CI their additional
		// checks may cause the tests to fail (such as those from IPReputation).
		$this->clearHook( 'ConfirmEditTriggersCaptcha' );

		$this->overrideConfigValues( [
			'CaptchaClass' => SimpleCaptcha::class,
			'CaptchaBadLoginAttempts' => 1,
			'CaptchaBadLoginPerUserAttempts' => 1,
			'CaptchaStorageClass' => CaptchaHashStore::class,
			'CaptchaTriggers' => [
				'createaccount' => true,
				'badlogin' => true,
				'badloginperuser' => true,
			],
		] );
		self::clearCaptchaFactoryGlobalInstances();
		CaptchaStore::unsetInstanceForTests();
	}

	public function tearDown(): void {
		parent::tearDown();
		self::clearCaptchaFactoryGlobalInstances();
	}

	/**
	 * @dataProvider provideGetAuthenticationRequests
	 */
	public function testGetAuthenticationRequests(
		$action, $useExistingUserOrNull, $triggers, $needsCaptcha, $preTestCallback = null
	) {
		if ( $useExistingUserOrNull === true ) {
			$username = $this->getTestSysop()->getUserIdentity()->getName();
		} elseif ( $useExistingUserOrNull === false ) {
			$username = 'Foo';
		} else {
			$username = null;
		}
		$this->setTriggers( $triggers );
		if ( $preTestCallback ) {
			$fn = array_shift( $preTestCallback );
			$this->$fn( ...$preTestCallback );
		}

		/** @var FauxRequest $request */
		$request = RequestContext::getMain()->getRequest();
		$request->setCookie( 'UserName', $username );

		$provider = new CaptchaPreAuthenticationProvider(
			$this->getServiceContainer()->get( 'ConfirmEditLoginAttemptCounterFactory' ),
			$this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' )
		);
		$this->initProvider( $provider, null, null, $this->getServiceContainer()->getAuthManager() );
		$reqs = $provider->getAuthenticationRequests( $action, [ 'username' => $username ] );
		if ( $needsCaptcha ) {
			$this->assertCount( 1, $reqs );
			$this->assertInstanceOf( CaptchaAuthenticationRequest::class, $reqs[0] );
		} else {
			$this->assertSame( [], $reqs );
		}
	}

	public static function provideGetAuthenticationRequests() {
		return [
			[ AuthManager::ACTION_LOGIN, null, [], false ],
			[ AuthManager::ACTION_LOGIN, null, [ 'badlogin' ], false ],
			[ AuthManager::ACTION_LOGIN, null, [ 'badlogin' ], true, [ 'blockLogin', 'Foo' ] ],
			[ AuthManager::ACTION_LOGIN, null, [ 'badloginperuser' ], false, [ 'blockLogin', 'Foo' ] ],
			[ AuthManager::ACTION_LOGIN, false, [ 'badloginperuser' ], false, [ 'blockLogin', 'Bar' ] ],
			[ AuthManager::ACTION_LOGIN, false, [ 'badloginperuser' ], true, [ 'blockLogin', 'Foo' ] ],
			[ AuthManager::ACTION_LOGIN, null, [ 'badloginperuser' ], true, [ 'flagSession' ] ],
			[ AuthManager::ACTION_CREATE, null, [], false ],
			[ AuthManager::ACTION_CREATE, null, [ 'createaccount' ], true ],
			[ AuthManager::ACTION_CREATE, true, [ 'createaccount' ], false ],
			[ AuthManager::ACTION_LINK, null, [], false ],
			[ AuthManager::ACTION_CHANGE, null, [], false ],
			[ AuthManager::ACTION_REMOVE, null, [], false ],
		];
	}

	public function testGetAuthenticationRequests_store() {
		$this->setTriggers( [ 'createaccount' ] );
		$captcha = new SimpleCaptcha();
		$provider = new CaptchaPreAuthenticationProvider(
			$this->getServiceContainer()->get( 'ConfirmEditLoginAttemptCounterFactory' ),
			$this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' )
		);
		$this->initProvider( $provider, null, null, $this->getServiceContainer()->getAuthManager() );

		$reqs = $provider->getAuthenticationRequests( AuthManager::ACTION_CREATE,
			[ 'username' => 'Foo' ] );

		$this->assertCount( 1, $reqs );
		/** @var CaptchaAuthenticationRequest $req */
		$req = $reqs[0];
		$this->assertInstanceOf( CaptchaAuthenticationRequest::class, $req );

		$id = $req->captchaId;
		$data = $req->captchaData;
		$this->assertEquals( $captcha->retrieveCaptcha( $id ), $data + [ 'index' => $id ] );
	}

	/**
	 * @dataProvider provideTestForAuthentication
	 */
	public function testTestForAuthentication( $req, $isBadLoginTriggered,
		$isBadLoginPerUserTriggered, $result, $expectedMessageKey = null
	) {
		$this->setTemporaryHook( 'PingLimiter', static function ( $user, $action, &$result ) {
			$result = false;
			return false;
		} );
		CaptchaStore::get()->store( '345', [ 'question' => '2+2', 'answer' => '4' ] );
		$loginAttemptCounter = $this->getMockBuilder( LoginAttemptCounter::class )
			->onlyMethods( [ 'isBadLoginTriggered', 'isBadLoginPerUserTriggered' ] )
			->disableOriginalConstructor()
			->getMock();
		$loginAttemptCounter->expects( $this->any() )->method( 'isBadLoginTriggered' )
			->willReturn( $isBadLoginTriggered );
		$loginAttemptCounter->expects( $this->any() )->method( 'isBadLoginPerUserTriggered' )
			->willReturn( $isBadLoginPerUserTriggered );
		$provider = $this->getProvider( $loginAttemptCounter );
		$this->initProvider( $provider, null, null, $this->getServiceContainer()->getAuthManager() );

		$status = $provider->testForAuthentication( $req ? [ $req ] : [] );

		$this->assertEquals( $result, $status->isGood() );
		if ( $expectedMessageKey !== null ) {
			$this->assertStatusError( $expectedMessageKey, $status );
		}
	}

	public static function provideTestForAuthentication() {
		$fallback = new UsernameAuthenticationRequest();
		$fallback->username = 'Foo';
		return [
			// [ auth request, bad login?, bad login per user?, result, expected error message key ]
			'no need to check' => [ $fallback, false, false, true ],
			'badlogin, no captcha submitted' => [ $fallback, true, false, false, 'captcha-login-required' ],
			'badloginperuser, no username' => [ null, false, true, true ],
			'badloginperuser, no captcha submitted' => [ $fallback, false, true, false, 'captcha-login-required' ],
			'captcha field present but empty' =>
				[ self::getCaptchaRequest( '345', '' ), true, true, false, 'captcha-login-required' ],
			'non-existent captcha' => [ self::getCaptchaRequest( '123', '4' ), true, true, false, 'wrongpassword' ],
			'wrong captcha' => [ self::getCaptchaRequest( '345', '6' ), true, true, false, 'wrongpassword' ],
			'correct captcha' => [ self::getCaptchaRequest( '345', '4' ), true, true, true ],
		];
	}

	public function testForAccountCreationWhenTurnstileHasError(): void {
		$this->overrideConfigValue(
			'CaptchaTriggers',
			[ CaptchaTriggers::CREATE_ACCOUNT => [ 'trigger' => true, 'class' => 'Turnstile' ] ]
		);

		/** @var CaptchaFactory $captchaFactory */
		$captchaFactory = $this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' );
		$captcha = $captchaFactory->getGlobalInstance( CaptchaTriggers::CREATE_ACCOUNT );
		TestingAccessWrapper::newFromObject( $captcha )->error = 'test-error';

		$provider = $this->getProvider( new LoginAttemptCounter( $captcha ) );
		$this->initProvider( $provider, null, null, $this->getServiceContainer()->getAuthManager() );

		$user = $this->getTestUser()->getUser();
		$status = $provider->testForAccountCreation( $user, $user, [] );

		$this->assertStatusError( 'captcha-error', $status );
		$this->assertSame( [ 'test-error' ], $status->getMessages()[0]->getParams() );
	}

	public function testForAccountCreationWhenHCaptchaHasInternalError(): void {
		$this->overrideConfigValue(
			'CaptchaTriggers',
			[ CaptchaTriggers::CREATE_ACCOUNT => [ 'trigger' => true, 'class' => 'HCaptcha' ] ]
		);

		/** @var CaptchaFactory $captchaFactory */
		$captchaFactory = $this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' );
		$captcha = $captchaFactory->getGlobalInstance( CaptchaTriggers::CREATE_ACCOUNT );
		TestingAccessWrapper::newFromObject( $captcha )->error = 'sitekey-mismatch';

		$provider = $this->getProvider( new LoginAttemptCounter( $captcha ) );
		$this->initProvider( $provider, null, null, $this->getServiceContainer()->getAuthManager() );

		$user = $this->getTestUser()->getUser();
		$status = $provider->testForAccountCreation( $user, $user, [] );

		$this->assertStatusError( 'hcaptcha-internal-error', $status );
	}

	/**
	 * @dataProvider provideTestForAccountCreation
	 */
	public function testTestForAccountCreation( $req, $creatorIsSysop, $result, $disableTrigger = false ) {
		$this->setTemporaryHook( 'PingLimiter', static function ( $user, $action, &$result ) {
			$result = false;
			return false;
		} );
		$this->setTriggers( $disableTrigger ? [] : [ 'createaccount' ] );
		CaptchaStore::get()->store( '345', [ 'question' => '2+2', 'answer' => '4' ] );
		$user = User::newFromName( 'Foo' );
		$provider = new CaptchaPreAuthenticationProvider(
			$this->getServiceContainer()->get( 'ConfirmEditLoginAttemptCounterFactory' ),
			$this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' )
		);
		$this->initProvider( $provider, null, null, $this->getServiceContainer()->getAuthManager() );

		$creator = $creatorIsSysop ? $this->getTestSysop()->getUser() : User::newFromName( 'Bar' );
		$status = $provider->testForAccountCreation( $user, $creator, $req ? [ $req ] : [] );
		$this->assertEquals( $result, $status->isGood() );
	}

	public static function provideTestForAccountCreation() {
		return [
			// [ auth request, creator, result, disable trigger? ]
			'no captcha' => [ null, false, false ],
			'non-existent captcha' => [ self::getCaptchaRequest( '123', '4' ), false, false ],
			'wrong captcha' => [ self::getCaptchaRequest( '345', '6' ), false, false ],
			'correct captcha' => [ self::getCaptchaRequest( '345', '4' ), false, true ],
			'user is exempt' => [ null, true, true ],
			'disabled' => [ null, false, true, 'disable' ],
		];
	}

	public function testPostAuthentication(): void {
		$this->setTriggers( [ 'badlogin', 'badloginperuser' ] );

		$badLoginCaptcha = new SimpleCaptcha();
		$badLoginPerUserCaptcha = new SimpleCaptcha();
		$loginAttemptCaptcha = new SimpleCaptcha();

		$user = $this->getServiceContainer()->getUserFactory()->newFromName( 'Foo' );
		$anotherUser = $this->getServiceContainer()->getUserFactory()->newFromName( 'Bar' );
		$loginAttemptCounter = new LoginAttemptCounter( $badLoginCaptcha );

		$mockCaptchaFactory = $this->createMock( CaptchaFactory::class );
		$mockCaptchaFactory->method( 'getGlobalInstance' )
			->willReturnMap( [
				[ CaptchaTriggers::LOGIN_ATTEMPT, $loginAttemptCaptcha ],
				[ CaptchaTriggers::BAD_LOGIN, $badLoginCaptcha ],
				[ CaptchaTriggers::BAD_LOGIN_PER_USER, $badLoginPerUserCaptcha ],
			] );

		$provider = $this->getProvider( $loginAttemptCounter, $mockCaptchaFactory );
		$this->initProvider( $provider, null, null, $this->getServiceContainer()->getAuthManager() );

		$this->assertFalse( $loginAttemptCounter->isBadLoginTriggered() );
		$this->assertFalse( $loginAttemptCounter->isBadLoginPerUserTriggered( $user ) );

		TestingAccessWrapper::newFromObject( $badLoginCaptcha )->setCaptchaSolved( true );
		TestingAccessWrapper::newFromObject( $loginAttemptCaptcha )->setCaptchaSolved( true );
		TestingAccessWrapper::newFromObject( $badLoginPerUserCaptcha )->setCaptchaSolved( true );

		$provider->postAuthentication( $user, AuthenticationResponse::newFail(
			wfMessage( '?' ) ) );

		$reqs = $provider->getAuthenticationRequests( AuthManager::ACTION_LOGIN, [ 'username' => $user->getName() ] );
		$this->assertNotEmpty(
			array_filter( $reqs, static fn ( $r ) => $r instanceof CaptchaAuthenticationRequest ),
			'CAPTCHA must be re-offered after a failed login'
		);

		$this->assertTrue( $loginAttemptCounter->isBadLoginTriggered() );
		$this->assertTrue( $loginAttemptCounter->isBadLoginPerUserTriggered( $user ) );
		$this->assertFalse( $loginAttemptCounter->isBadLoginPerUserTriggered( $anotherUser ) );
		$this->assertNull( $badLoginCaptcha->isCaptchaSolved() );
		$this->assertNull( $loginAttemptCaptcha->isCaptchaSolved() );
		$this->assertNull( $badLoginPerUserCaptcha->isCaptchaSolved() );

		$provider->postAuthentication( $user, AuthenticationResponse::newPass( 'Foo' ) );

		$this->assertFalse( $loginAttemptCounter->isBadLoginPerUserTriggered( $user ) );
	}

	public function testPostAuthentication_disabled() {
		$this->setTriggers( [] );
		$captcha = new SimpleCaptcha();

		/** @var LoginAttemptCounterFactory $loginAttemptCounterFactory */
		$loginAttemptCounterFactory = $this->getServiceContainer()->get( 'ConfirmEditLoginAttemptCounterFactory' );
		$loginAttemptCounter = $loginAttemptCounterFactory->newLoginAttemptCounter( $captcha );

		$user = User::newFromName( 'Foo' );
		$provider = $this->getProvider( $loginAttemptCounter );
		$this->initProvider( $provider, null, null, $this->getServiceContainer()->getAuthManager() );

		$this->assertFalse( $loginAttemptCounter->isBadLoginTriggered() );
		$this->assertFalse( $loginAttemptCounter->isBadLoginPerUserTriggered( $user ) );

		$provider->postAuthentication( $user, AuthenticationResponse::newFail(
			wfMessage( '?' ) ) );

		$this->assertFalse( $loginAttemptCounter->isBadLoginTriggered() );
		$this->assertFalse( $loginAttemptCounter->isBadLoginPerUserTriggered( $user ) );
	}

	/** @dataProvider providePostAccountCreation */
	public function testPostAccountCreation(
		callable $authenticationResponseCallback,
		?bool $isCaptchaSolvedExpectedValue
	): void {
		/** @var CaptchaFactory $captchaFactory */
		$captchaFactory = $this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' );
		$provider = new CaptchaPreAuthenticationProvider(
			$this->getServiceContainer()->get( 'ConfirmEditLoginAttemptCounterFactory' ),
			$captchaFactory
		);
		$this->initProvider( $provider, null, null, $this->getServiceContainer()->getAuthManager() );

		$captcha = $captchaFactory->getGlobalInstance( CaptchaTriggers::CREATE_ACCOUNT );
		TestingAccessWrapper::newFromObject( $captcha )->setCaptchaSolved( true );
		$this->assertTrue( $captcha->isCaptchaSolved() );

		$user = $this->getServiceContainer()->getUserFactory()->newFromName( 'Foo' );
		$provider->postAccountCreation( $user, $user, $authenticationResponseCallback() );

		$this->assertSame( $isCaptchaSolvedExpectedValue, $captcha->isCaptchaSolved() );
	}

	public function providePostAccountCreation(): array {
		return [
			'Failed account creation' => [
				'authenticationResponseCallback' => static fn () => AuthenticationResponse::newFail(
					wfMessage( '?' )
				),
				'isCaptchaSolvedExpectedValue' => null,
			],
			'Successful account creation' => [
				'authenticationResponseCallback' => static fn () => AuthenticationResponse::newPass( 'Foo' ),
				'isCaptchaSolvedExpectedValue' => true,
			],
		];
	}

	/**
	 * @dataProvider providePingLimiter
	 */
	public function testPingLimiter( array $attempts ) {
		$this->mergeMwGlobalArrayValue(
			'wgRateLimits',
			[
				'badcaptcha' => [
					'user' => [ 1, 1 ],
				],
			]
		);
		$provider = new CaptchaPreAuthenticationProvider(
			$this->getServiceContainer()->get( 'ConfirmEditLoginAttemptCounterFactory' ),
			$this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' )
		);
		$this->initProvider( $provider, null, null, $this->getServiceContainer()->getAuthManager() );
		/** @var CaptchaPreAuthenticationProvider $providerAccess */
		$providerAccess = TestingAccessWrapper::newFromObject( $provider );

		$disablePingLimiter = false;
		$this->setTemporaryHook( 'PingLimiter',
			static function ( &$user, $action, &$result ) use ( &$disablePingLimiter ) {
				if ( $disablePingLimiter ) {
					$result = false;
					return false;
				}
				return null;
			}
		);
		foreach ( $attempts as $attempt ) {
			$disablePingLimiter = !empty( $attempts[3] );
			$captcha = new SimpleCaptcha();
			CaptchaStore::get()->store( '345', [ 'question' => '7+7', 'answer' => '14' ] );
			$success = $providerAccess->verifyCaptcha( $captcha, [ $attempts[0] ], $attempts[1] );
			$this->assertEquals( $attempts[2], $success );
		}
	}

	public static function providePingLimiter() {
		$sysop = User::newFromName( 'UTSysop' );
		return [
			// sequence of [ auth request, user, result, disable ping limiter? ]
			'no failure' => [
				[ self::getCaptchaRequest( '345', '14' ), new User(), true ],
				[ self::getCaptchaRequest( '345', '14' ), new User(), true ],
			],
			'limited' => [
				[ self::getCaptchaRequest( '345', '33' ), new User(), false ],
				[ self::getCaptchaRequest( '345', '14' ), new User(), false ],
			],
			'exempt user' => [
				[ self::getCaptchaRequest( '345', '33' ), $sysop, false ],
				[ self::getCaptchaRequest( '345', '14' ), $sysop, true ],
			],
			'pinglimiter disabled' => [
				[ self::getCaptchaRequest( '345', '33' ), new User(), false, 'disable' ],
				[ self::getCaptchaRequest( '345', '14' ), new User(), true, 'disable' ],
			],
		];
	}

	protected static function getCaptchaRequest( $id, $word, $username = null ) {
		$req = new CaptchaAuthenticationRequest( $id, [ 'question' => '?', 'answer' => $word ] );
		$req->captchaWord = $word;
		$req->username = $username;
		return $req;
	}

	protected function blockLogin( $username ) {
		/** @var LoginAttemptCounterFactory $loginAttemptCounterFactory */
		$loginAttemptCounterFactory = $this->getServiceContainer()->get( 'ConfirmEditLoginAttemptCounterFactory' );
		$loginAttemptCounter = $loginAttemptCounterFactory->newLoginAttemptCounter( new SimpleCaptcha() );
		$loginAttemptCounter->increaseBadLoginCounter( $username );
	}

	protected function flagSession() {
		RequestContext::getMain()->getRequest()->getSession()
			->set( 'ConfirmEdit:loginCaptchaPerUserTriggered', true );
	}

	protected function setTriggers( $triggers ) {
		$types = [ 'edit', 'create', 'sendemail', 'addurl', 'createaccount', 'badlogin',
			'badloginperuser' ];
		$captchaTriggers = array_combine( $types, array_map( static function ( $type ) use ( $triggers ) {
			return in_array( $type, $triggers, true );
		}, $types ) );
		$this->overrideConfigValue( 'CaptchaTriggers', $captchaTriggers );
	}

	private function getProvider(
		LoginAttemptCounter $loginAttemptCounter,
		?CaptchaFactory $captchaFactory = null
	): CaptchaPreAuthenticationProvider {
		$mockLoginAttemptCounterFactory = $this->createMock( LoginAttemptCounterFactory::class );
		$mockLoginAttemptCounterFactory->method( 'newLoginAttemptCounter' )
			->willReturn( $loginAttemptCounter );

		return new CaptchaPreAuthenticationProvider(
			$mockLoginAttemptCounterFactory,
			$captchaFactory ?? $this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' )
		);
	}

}
