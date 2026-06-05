<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration;

use MediaWiki\Config\HashConfig;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\AbuseFilter\Consequences\Parameters;
use MediaWiki\Extension\AbuseFilter\Filter\ExistingFilter;
use MediaWiki\Extension\ConfirmEdit\AbuseFilter\CaptchaConsequence;
use MediaWiki\Extension\ConfirmEdit\AbuseFilterHooks;
use MediaWiki\Extension\ConfirmEdit\CaptchaTriggers;
use MediaWiki\Extension\ConfirmEdit\Services\CaptchaFactory;
use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\Session\Session;
use MediaWiki\User\UserIdentity;
use MediaWiki\User\UserIdentityValue;
use MediaWikiIntegrationTestCase;
use TestLogger;
use Wikimedia\TestingAccessWrapper;
use Wikimedia\Timestamp\ConvertibleTimestamp;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\AbuseFilter\CaptchaConsequence
 * @covers \MediaWiki\Extension\ConfirmEdit\AbuseFilterHooks
 */
class AbuseFilterTest extends MediaWikiIntegrationTestCase {
	use CaptchaTestHelperTrait;

	public static function setUpBeforeClass(): void {
		// Cannot use markTestSkippedIfExtensionNotLoaded() because we need to skip the entire class.
		// Specifically, skip MediaWikiCoversValidator because AbuseFilterHooks.php fails.
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'Abuse Filter' ) ) {
			self::markTestSkipped( "AbuseFilter extension is required for this test" );
		}
	}

	public function setUp(): void {
		parent::setUp();

		self::clearCaptchaFactoryGlobalInstances();

		$this->getSession()->remove(
			SimpleCaptcha::ABUSEFILTER_CAPTCHA_CONSEQUENCE_SESSION_KEY
		);
		$this->getSession()->remove(
			CaptchaConsequence::FILTER_ID_SESSION_KEY
		);
	}

	public function testOnAbuseFilterCustomActions() {
		$config = new HashConfig( [ 'ConfirmEditEnabledAbuseFilterCustomActions' => [ 'showcaptcha' ] ] );
		$abuseFilterHooks = new AbuseFilterHooks(
			$config,
			$this->getServiceContainer()->getHookContainer(),
			$this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' )
		);
		$actions = [];
		$abuseFilterHooks->onAbuseFilterCustomActions( $actions );
		$this->assertArrayHasKey( 'showcaptcha', $actions );
	}

	public function testConsequence() {
		$parameters = $this->createMock( Parameters::class );
		$parameters->method( 'getAction' )->willReturn( 'edit' );
		/** @var CaptchaFactory $captchaFactory */
		$captchaFactory = $this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' );
		$simpleCaptcha = $captchaFactory->getGlobalInstance( CaptchaTriggers::EDIT );
		$this->assertForceCaptchaNotSet( $simpleCaptcha );
		$this->getCaptchaConsequence( $parameters )->execute();
		$this->assertTrue( $simpleCaptcha->shouldForceShowCaptcha() );
	}

	public function testConsequenceActionDoesNotMatch() {
		$logger = new TestLogger( true );
		$this->setLogger( 'ConfirmEdit', $logger );
		$parameters = $this->createMock( Parameters::class );
		$parameters->method( 'getAction' )->willReturn( 'foo' );

		/** @var CaptchaFactory $captchaFactory */
		$captchaFactory = $this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' );
		$simpleCaptcha = $captchaFactory->getGlobalInstance( 'bar' );

		$this->getCaptchaConsequence( $parameters )->execute();
		$this->assertForceCaptchaNotSet( $simpleCaptcha );
		$this->assertEquals(
			'Filter {filter}: {action} is not defined in the list of triggers known to ConfirmEdit',
			$logger->getBuffer()[0][1]
		);
	}

	public function testConsequenceCaptchaAlreadySolved(): void {
		$this->overrideConfigValue(
			'CaptchaTriggers',
			[ CaptchaTriggers::EDIT => [ 'trigger' => true, 'class' => 'SimpleCaptcha' ] ]
		);
		self::clearCaptchaFactoryGlobalInstances();
		$userIdentity = new UserIdentityValue( 123, 'TestUser' );

		$parameters = $this->createMock( Parameters::class );
		$parameters->method( 'getAction' )->willReturn( 'edit' );
		$parameters->method( 'getUser' )->willReturn( $userIdentity );

		/** @var CaptchaFactory $captchaFactory */
		$captchaFactory = $this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' );
		$simpleCaptcha = $captchaFactory->getGlobalInstance( CaptchaTriggers::EDIT );
		$simpleCaptcha = TestingAccessWrapper::newFromObject( $simpleCaptcha );
		$simpleCaptcha->setCaptchaSolved( true );

		$this->assertFalse( $this->getCaptchaConsequence( $parameters )->execute() );
	}

	public function testConsequenceWhenHCaptchaSolvedButAlwaysChallengeSiteKeyDefined(): void {
		$this->overrideConfigValue(
			'CaptchaTriggers',
			[
				CaptchaTriggers::EDIT => [
					'trigger' => true,
					'class' => 'HCaptcha',
					'config' => [
						'HCaptchaAlwaysChallengeSiteKey' => 'always-challenge-sitekey',
					],
				],
			]
		);

		$userIdentity = new UserIdentityValue( 123, 'TestUser' );

		$parameters = $this->createMock( Parameters::class );
		$parameters->method( 'getAction' )->willReturn( 'edit' );
		$parameters->method( 'getUser' )->willReturn( $userIdentity );

		/** @var CaptchaFactory $captchaFactory */
		$captchaFactory = $this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' );
		$simpleCaptcha = $captchaFactory->getGlobalInstance( CaptchaTriggers::EDIT );
		$simpleCaptcha = TestingAccessWrapper::newFromObject( $simpleCaptcha );
		$simpleCaptcha->setCaptchaSolved( true );

		$this->assertTrue( $this->getCaptchaConsequence( $parameters )->execute() );
		$this->assertTrue( $simpleCaptcha->shouldForceShowCaptcha() );
	}

	public function testConsequenceWhenHookAborts(): void {
		$userIdentity = new UserIdentityValue( 123, 'TestUser' );

		$parameters = $this->createMock( Parameters::class );
		$parameters->method( 'getAction' )->willReturn( 'edit' );
		$parameters->method( 'getUser' )->willReturn( $userIdentity );

		/** @var CaptchaFactory $captchaFactory */
		$captchaFactory = $this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' );
		$simpleCaptcha = $captchaFactory->getGlobalInstance( CaptchaTriggers::EDIT );

		$this->setTemporaryHook(
			'ConfirmEditBeforeForceShowCaptcha',
			function ( UserIdentity $actualUserIdentity, string $action ) use (
				$userIdentity
			) {
				$this->assertSame( $userIdentity, $actualUserIdentity );
				$this->assertSame( CaptchaTriggers::EDIT, $action );
				return false;
			}
		);

		$this->getCaptchaConsequence( $parameters )->execute();
		$this->assertForceCaptchaNotSet( $simpleCaptcha );
	}

	public function testConsequenceSetsSessionKeyOnMatch() {
		ConvertibleTimestamp::setFakeTime( 1750000000 );
		$this->overrideConfigValue(
			'CaptchaAbuseFilterCaptchaConsequenceTTL',
			600
		);

		$filter = $this->createMock( ExistingFilter::class );
		$filter
			->expects( $this->once() )
			->method( 'getID' )
			->willReturn( 123 );

		$parameters = $this->createMock( Parameters::class );
		$parameters
			->method( 'getAction' )
			->willReturn( 'edit' );
		$parameters
			->method( 'getFilter' )
			->willReturn( $filter );

		/** @var CaptchaFactory $captchaFactory */
		$captchaFactory = $this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' );
		$simpleCaptcha = $captchaFactory->getGlobalInstance( CaptchaTriggers::EDIT );
		$this->assertForceCaptchaNotSet( $simpleCaptcha );

		$this->getCaptchaConsequence( $parameters )->execute();

		$this->assertTrue( $simpleCaptcha->shouldForceShowCaptcha() );
		$this->assertEquals(
			// current timestamp + 10 minutes
			1750000600,
			$this->getSession()->get(
				SimpleCaptcha::ABUSEFILTER_CAPTCHA_CONSEQUENCE_SESSION_KEY
			)
		);
		$this->assertEquals(
			123,
			$this->getSession()->get(
				CaptchaConsequence::FILTER_ID_SESSION_KEY
			)
		);
	}

	private function getCaptchaConsequence( Parameters $parameters ): CaptchaConsequence {
		return new CaptchaConsequence(
			$parameters,
			$this->getServiceContainer()->getHookContainer(),
			$this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' )
		);
	}

	private function assertForceCaptchaNotSet( SimpleCaptcha $simpleCaptcha ): void {
		$this->assertFalse( $simpleCaptcha->shouldForceShowCaptcha() );
		$this->assertFalse(
			$this->getSession()->exists(
				SimpleCaptcha::ABUSEFILTER_CAPTCHA_CONSEQUENCE_SESSION_KEY
			)
		);
		$this->assertFalse( $this->getSession()->exists( CaptchaConsequence::FILTER_ID_SESSION_KEY ) );
	}

	private function getSession(): Session {
		return RequestContext::getMain()->getRequest()->getSession();
	}
}
