<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration\Services;

use MediaWiki\Context\IContextSource;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\ConfirmEdit\Auth\LoginAttemptCounter;
use MediaWiki\Extension\ConfirmEdit\Auth\LoginAttemptCounterFactory;
use MediaWiki\Extension\ConfirmEdit\CaptchaTriggers;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\HCaptcha;
use MediaWiki\Extension\ConfirmEdit\Services\CaptchaFactory;
use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\Services\CaptchaFactory
 * @group Database
 */
class CaptchaFactoryTest extends MediaWikiIntegrationTestCase {
	protected function setUp(): void {
		parent::setUp();

		$this->getCaptchaFactory()->unsetGlobalInstancesForTests();
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
				'title' => Title::newFromText( 'Special:CreateAccount' ),
				'requestActionName' => '',
				'expectedAction' => CaptchaTriggers::CREATE_ACCOUNT,
			],
			'Request is for the accountcreation API' => [
				'title' => Title::newFromText( 'Ignored' ),
				'requestActionName' => 'createaccount',
				'expectedAction' => CaptchaTriggers::CREATE_ACCOUNT,
			],
			'Title is Special:UserLogin without badlogin triggered' => [
				'title' => Title::newFromText( 'Special:UserLogin' ),
				'requestActionName' => '',
				'expectedAction' => CaptchaTriggers::LOGIN_ATTEMPT
			],
			'Request is for the login API without badlogin triggered' => [
				'title' => Title::newFromText( 'Ignored' ),
				'requestActionName' => 'login',
				'expectedAction' => CaptchaTriggers::LOGIN_ATTEMPT
			],
			'Request is for the clientlogin API without badlogin triggered' => [
				'title' => Title::newFromText( 'Ignored' ),
				'requestActionName' => 'clientlogin',
				'expectedAction' => CaptchaTriggers::LOGIN_ATTEMPT
			],
			'Title is Special:EmailUser' => [
				'title' => Title::newFromText( 'Special:EmailUser' ),
				'requestActionName' => '',
				'expectedAction' => CaptchaTriggers::SENDEMAIL,
			],
			'Request is for the emailuser API' => [
				'title' => Title::newFromText( 'Ignored' ),
				'requestActionName' => 'emailuser',
				'expectedAction' => CaptchaTriggers::SENDEMAIL,
			],
		];
	}

	public function testGetGlobalInstanceFromContextForBadLoginFromSession(): void {
		$context = RequestContext::getMain();
		$context->getRequest()->getSession()->set( 'ConfirmEdit:loginCaptchaPerUserTriggered', true );

		$this->testGetGlobalInstanceFromContext(
			Title::newFromText( 'Special:UserLogin' ),
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
			Title::newFromText( 'Special:UserLogin' ),
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
			Title::newFromText( 'Special:UserLogin' ),
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
}
