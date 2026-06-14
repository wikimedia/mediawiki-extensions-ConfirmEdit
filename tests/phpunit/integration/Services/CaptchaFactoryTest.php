<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration\Services;

use MediaWiki\Auth\AuthManager;
use MediaWiki\Context\IContextSource;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\ConfirmEdit\Auth\CaptchaAuthenticationRequest;
use MediaWiki\Extension\ConfirmEdit\Auth\LoginAttemptCounter;
use MediaWiki\Extension\ConfirmEdit\Auth\LoginAttemptCounterFactory;
use MediaWiki\Extension\ConfirmEdit\CaptchaTriggers;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\HCaptcha;
use MediaWiki\Extension\ConfirmEdit\QuestyCaptcha\QuestyCaptcha;
use MediaWiki\Extension\ConfirmEdit\Services\CaptchaFactory;
use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
use MediaWiki\Extension\ConfirmEdit\Tests\Integration\CaptchaTestHelperTrait;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\Services\CaptchaFactory
 * @group Database
 */
class CaptchaFactoryTest extends MediaWikiIntegrationTestCase {
	use CaptchaTestHelperTrait;

	protected function setUp(): void {
		parent::setUp();
		self::clearCaptchaFactoryGlobalInstances();
	}

	private function getCaptchaFactory(): CaptchaFactory {
		return $this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' );
	}

	/** @dataProvider provideGetGlobalInstanceFromContext */
	public function testGetGlobalInstanceFromContext(
		Title $title,
		string $requestActionName,
		string $expectedAction,
		?IContextSource $context = null
	): void {
		$context ??= RequestContext::getMain();
		$context->setTitle( $title );
		$context->setActionName( $requestActionName );

		// We cannot access the action inside the returned SimpleCaptcha instance, so read it by storing the action
		// provided by the ConfirmEditCaptchaClass hook (which is fired during ::getGlobalInstance)
		$lastHookProvidedAction = '';
		$this->setTemporaryHook(
			'ConfirmEditCaptchaClass',
			static function ( $action ) use ( &$lastHookProvidedAction ) {
				$lastHookProvidedAction = $action;
			}
		);

		$this->assertInstanceOf(
			SimpleCaptcha::class,
			$this->getCaptchaFactory()->getGlobalInstanceFromContext( $context )
		);
		$this->assertSame( $expectedAction, $lastHookProvidedAction );
	}

	public static function provideGetGlobalInstanceFromContext(): array {
		return [
			'Title is Special:CreateAccount' => [
				'title' => Title::makeTitle( NS_SPECIAL, 'CreateAccount' ),
				'requestActionName' => '',
				'expectedAction' => CaptchaTriggers::CREATE_ACCOUNT,
			],
			'Request is for the accountcreation API' => [
				'title' => Title::makeTitle( NS_MAIN, 'Ignored' ),
				'requestActionName' => 'createaccount',
				'expectedAction' => CaptchaTriggers::CREATE_ACCOUNT,
			],
			'Title is Special:UserLogin without badlogin triggered' => [
				'title' => Title::makeTitle( NS_SPECIAL, 'UserLogin' ),
				'requestActionName' => '',
				'expectedAction' => CaptchaTriggers::LOGIN_ATTEMPT
			],
			'Request is for the login API without badlogin triggered' => [
				'title' => Title::makeTitle( NS_MAIN, 'Ignored' ),
				'requestActionName' => 'login',
				'expectedAction' => CaptchaTriggers::LOGIN_ATTEMPT
			],
			'Request is for the clientlogin API without badlogin triggered' => [
				'title' => Title::makeTitle( NS_MAIN, 'Ignored' ),
				'requestActionName' => 'clientlogin',
				'expectedAction' => CaptchaTriggers::LOGIN_ATTEMPT
			],
			'Title is Special:EmailUser' => [
				'title' => Title::makeTitle( NS_SPECIAL, 'EmailUser' ),
				'requestActionName' => '',
				'expectedAction' => CaptchaTriggers::SENDEMAIL,
			],
			'Request is for the emailuser API' => [
				'title' => Title::makeTitle( NS_MAIN, 'Ignored' ),
				'requestActionName' => 'emailuser',
				'expectedAction' => CaptchaTriggers::SENDEMAIL,
			],
		];
	}

	public function testGetGlobalInstanceFromContextForBadLoginFromSession(): void {
		$context = RequestContext::getMain();
		$context->getRequest()->getSession()->set( 'ConfirmEdit:loginCaptchaPerUserTriggered', true );

		$this->testGetGlobalInstanceFromContext(
			Title::makeTitle( NS_SPECIAL, 'UserLogin' ),
			'',
			CaptchaTriggers::BAD_LOGIN_PER_USER,
			$context
		);
	}

	public function testGetGlobalInstanceFromContextForBadLoginFromCounter(): void {
		$mockLoginAttemptCounter = $this->createMock( LoginAttemptCounter::class );
		$mockLoginAttemptCounter->method( 'isBadLoginTriggered' )
			->willReturn( true );

		$mockLoginAttemptCounterFactory = $this->createMock( LoginAttemptCounterFactory::class );
		$mockLoginAttemptCounterFactory->method( 'newLoginAttemptCounter' )
			->willReturn( $mockLoginAttemptCounter );
		$this->setService( 'ConfirmEditLoginAttemptCounterFactory', $mockLoginAttemptCounterFactory );

		$this->testGetGlobalInstanceFromContext(
			Title::makeTitle( NS_SPECIAL, 'UserLogin' ),
			'',
			CaptchaTriggers::BAD_LOGIN
		);
	}

	public function testGetGlobalInstanceFromContextForBadLoginFromUserCounter(): void {
		$context = RequestContext::getMain();
		$context->getRequest()->setCookie( 'UserName', 'TestUserAbc' );

		$mockLoginAttemptCounter = $this->createMock( LoginAttemptCounter::class );
		$mockLoginAttemptCounter->method( 'isBadLoginPerUserTriggered' )
			->with( 'TestUserAbc' )
			->willReturn( true );

		$mockLoginAttemptCounterFactory = $this->createMock( LoginAttemptCounterFactory::class );
		$mockLoginAttemptCounterFactory->method( 'newLoginAttemptCounter' )
			->willReturn( $mockLoginAttemptCounter );
		$this->setService( 'ConfirmEditLoginAttemptCounterFactory', $mockLoginAttemptCounterFactory );

		$this->testGetGlobalInstanceFromContext(
			Title::makeTitle( NS_SPECIAL, 'UserLogin' ),
			'',
			CaptchaTriggers::BAD_LOGIN_PER_USER,
			$context
		);
	}

	public function testGetGlobalInstanceFromContextForExistingPage(): void {
		$this->testGetGlobalInstanceFromContext(
			$this->getExistingTestPage()->getTitle(),
			'edit',
			CaptchaTriggers::EDIT
		);
	}

	public function testGetGlobalInstanceFromContextForNonExistingPage(): void {
		$this->testGetGlobalInstanceFromContext(
			$this->getNonexistingTestPage()->getTitle(),
			'edit',
			CaptchaTriggers::CREATE
		);
	}

	/** @dataProvider provideGetGlobalInstanceFromAuthenticationRequest */
	public function testGetGlobalInstanceFromAuthenticationRequest(
		string $authenticationRequestAction,
		string $expectedAction
	): void {
		$captchaAuthenticationRequest = new CaptchaAuthenticationRequest( '', [] );
		$captchaAuthenticationRequest->action = $authenticationRequestAction;

		// We cannot access the action inside the returned SimpleCaptcha instance, so read it by storing the action
		// provided by the ConfirmEditCaptchaClass hook (which is fired during ::getGlobalInstance)
		$lastHookProvidedAction = '';
		$this->setTemporaryHook(
			'ConfirmEditCaptchaClass',
			static function ( $action ) use ( &$lastHookProvidedAction ) {
				$lastHookProvidedAction = $action;
			}
		);

		$this->assertInstanceOf(
			SimpleCaptcha::class,
			$this->getCaptchaFactory()->getGlobalInstanceFromAuthenticationRequest(
				$captchaAuthenticationRequest,
				RequestContext::getMain()->getRequest()->getSession()
			)
		);
		$this->assertSame( $expectedAction, $lastHookProvidedAction );
	}

	public static function provideGetGlobalInstanceFromAuthenticationRequest(): array {
		return [
			'Action is AuthManager::ACTION_LOGIN' => [ AuthManager::ACTION_LOGIN, CaptchaTriggers::LOGIN_ATTEMPT ],
			'Action is AuthManager::ACTION_CREATE' => [ AuthManager::ACTION_CREATE, CaptchaTriggers::CREATE_ACCOUNT ],
			'Action is not recognised' => [ 'unrecognised', '' ],
		];
	}

	public function testGetGlobalInstanceForBadLoginPerUserFallsBackToBadLoginClass(): void {
		$this->overrideConfigValues( [
			'CaptchaClass' => 'SimpleCaptcha',
			'CaptchaTriggers' => [ CaptchaTriggers::BAD_LOGIN => [ 'class' => 'HCaptcha' ] ],
		] );

		$this->assertInstanceOf(
			HCaptcha::class,
			$this->getCaptchaFactory()->getGlobalInstance( CaptchaTriggers::BAD_LOGIN_PER_USER ),
			'::getGlobalInstance should have defaulted to bad-login class over default class'
		);
	}

	public function testGetGlobalInstanceForCaptchaTriggers(): void {
		$this->overrideConfigValues( [
			'CaptchaClass' => 'SimpleCaptcha',
			'CaptchaTriggers' => [
				// New style trigger
				'edit' => [
					'trigger' => true,
					'class' => 'FancyCaptcha',
				],
				// Old style trigger
				'move' => false,
			]
		] );

		$captchaFactory = $this->getCaptchaFactory();
		$this->assertInstanceOf(
			SimpleCaptcha::class,
			$captchaFactory->getGlobalInstance(),
			'When no action specified, the CaptchaClass should be used'
		);
		$this->assertInstanceOf(
			SimpleCaptcha::class,
			$captchaFactory->getGlobalInstance( 'move' ),
			'When CaptchaTriggers defines trigger as boolean, the CaptchaClass should be used'
		);
		$this->assertInstanceOf(
			SimpleCaptcha::class,
			$captchaFactory->getGlobalInstance( 'foo' ),
			'When CaptchaTriggers is not recognised, the CaptchaClass should be used'
		);
		$this->assertInstanceOf(
			SimpleCaptcha::class,
			$captchaFactory->getGlobalInstance( 'edit' ),
			'When CaptchaTriggers defines a class, that class should be used'
		);
	}

	public function testOnConfirmEditHooksGetInstance(): void {
		$session = RequestContext::getMain()->getRequest()->getSession();
		$session->remove(
			SimpleCaptcha::ABUSEFILTER_CAPTCHA_CONSEQUENCE_SESSION_KEY
		);

		$this->overrideConfigValues( [
			'CaptchaClass' => 'SimpleCaptcha',
			'CaptchaTriggers' => [ 'createaccount' => [
				'trigger' => true,
				'class' => 'FancyCaptcha',
			] ]
		] );
		$this->setTemporaryHook( 'ConfirmEditCaptchaClass', static function ( $action, &$className ) {
			if ( $action === 'createaccount' ) {
				$className = 'HCaptcha';
			} elseif ( $action === 'edit' ) {
				$className = 'QuestyCaptcha';
			} elseif ( $action === 'badlogin' ) {
				$className = 'HCaptcha';
			}
		} );

		$captchaFactory = $this->getCaptchaFactory();
		$instance = $captchaFactory->getGlobalInstance( 'createaccount' );
		$this->assertInstanceOf( HCaptcha::class, $instance );
		$instance->setForceShowCaptcha( true );
		$newInstance = $captchaFactory->getGlobalInstance( 'createaccount' );
		$this->assertTrue(
			$newInstance->shouldForceShowCaptcha(),
			'Calling ::getInstance() again returns the cached instance'
		);

		// forceShowCaptcha is "sticky": Once set for an action (i.e. the above
		// call to setForceShowCaptcha), it will remain set for any action in
		// the current user session. That means checking it for this instance
		// ('badlogin') will still return true even if it was set for a different
		// instance ('createaccount').
		$instance = $captchaFactory->getGlobalInstance( 'badlogin' );
		$this->assertInstanceOf( HCaptcha::class, $instance );
		$this->assertTrue( $instance->shouldForceShowCaptcha() );

		// Once the static cache is removed and the session storage cleared,
		// a check on an instance for 'badlogin' will fall back to not forcing
		// showing the captcha.
		$session->remove(
			SimpleCaptcha::ABUSEFILTER_CAPTCHA_CONSEQUENCE_SESSION_KEY
		);
		$captchaFactory->unsetGlobalInstancesForTests();
		$instance = $captchaFactory->getGlobalInstance( 'badlogin' );
		$this->assertInstanceOf( HCaptcha::class, $instance );
		$this->assertFalse( $instance->shouldForceShowCaptcha() );

		$instance = $captchaFactory->getGlobalInstance( 'edit' );
		$this->assertInstanceOf( QuestyCaptcha::class, $instance );
		$this->assertFalse( $instance->shouldForceShowCaptcha() );

		$instance = $captchaFactory->getGlobalInstance( 'move' );
		$this->assertInstanceOf( SimpleCaptcha::class, $instance );
		$this->assertFalse( $instance->shouldForceShowCaptcha() );

		// Check that cached instance is returned when no action is specified.
		$instance = $captchaFactory->getGlobalInstance();
		$instance->setForceShowCaptcha( true );
		$this->assertInstanceOf( SimpleCaptcha::class, $instance );
		$instance = $captchaFactory->getGlobalInstance();
		$this->assertInstanceOf( SimpleCaptcha::class, $instance );
		$this->assertTrue( $instance->shouldForceShowCaptcha() );
	}
}
