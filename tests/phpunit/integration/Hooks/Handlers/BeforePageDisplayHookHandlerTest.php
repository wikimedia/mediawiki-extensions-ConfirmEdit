<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration\Hooks\Handlers;

use InvalidArgumentException;
use MediaWiki\Block\AnonIpBlockTarget;
use MediaWiki\Block\Block;
use MediaWiki\Block\RangeBlockTarget;
use MediaWiki\Context\RequestContext;
use MediaWiki\Deferred\DeferredUpdates;
use MediaWiki\Extension\ConfirmEdit\CaptchaTriggers;
use MediaWiki\Extension\ConfirmEdit\Hooks\Handlers\BeforePageDisplayHookHandler;
use MediaWiki\Extension\ConfirmEdit\Tests\Integration\CaptchaTestHelperTrait;
use MediaWiki\Extension\ConfirmEdit\Tests\Integration\HCaptchaBlockMockTrait;
use MediaWiki\Extension\GlobalBlocking\GlobalBlock;
use MediaWiki\Http\MWHttpRequest;
use MediaWiki\Json\FormatJson;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Skin\Skin;
use MediaWiki\Status\Status;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWikiIntegrationTestCase;
use MockHttpTrait;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\Hooks\Handlers\BeforePageDisplayHookHandler
 * @group Database
 */
class BeforePageDisplayHookHandlerTest extends MediaWikiIntegrationTestCase {
	use CaptchaTestHelperTrait;
	use HCaptchaBlockMockTrait;
	use MockHttpTrait;

	protected function tearDown(): void {
		self::clearCaptchaFactoryGlobalInstances();

		parent::tearDown();
	}

	/** @dataProvider provideOnBeforePageDisplay */
	public function testOnBeforePageDisplay(
		bool $expectedModulesAdded,
		?string $expectedSiteKey,
		string $action,
		bool $pageExists,
		string $editCaptchaClass,
		string $createCaptchaClass,
		?string $passiveModeSiteKey,
		bool $userCanSkipCaptcha,
		?string $blockType,
		array $skipUserAgents = [],
		?string $userAgent = null
	): void {
		if ( $pageExists ) {
			$title = $this->getExistingTestPage()->getTitle();
		} else {
			$title = $this->getNonexistingTestPage();
		}

		// Now clear the cache since it may have cached instances
		// created when the new page was inserted.
		self::clearCaptchaFactoryGlobalInstances();

		$this->overrideConfigValue(
			'HCaptchaBlockedIpEditingScoreCollectionSiteKey',
			$passiveModeSiteKey
		);
		$this->overrideConfigValue(
			'HCaptchaBlockedIpEditingScoreSkipUserAgents',
			$skipUserAgents
		);
		$this->overrideConfigValue(
			'CaptchaTriggers',
			[
				CaptchaTriggers::EDIT => [
					'trigger' => true,
					'class' => $editCaptchaClass,
					'config' => [
						'HCaptchaSiteKey' => 'should-not-be-used',
						'HCaptchaBlockedIpEditingScoreCollectionSiteKey' =>
							'should-not-be-used',
					],
				],
				CaptchaTriggers::CREATE => [
					'trigger' => true,
					'class' => $createCaptchaClass,
					'config' => [
						'HCaptchaSiteKey' => 'should-not-be-used',
						'HCaptchaBlockedIpEditingScoreCollectionSiteKey' =>
							'should-not-be-used',
					],
				],
			]
		);

		$request = new FauxRequest( [ 'action' => $action ] );
		if ( $userAgent !== null ) {
			$request->setHeader( 'User-Agent', $userAgent );
		}

		$context = RequestContext::getMain();
		$context->setRequest( $request );

		$mockBlock = null;
		if ( $blockType !== null ) {
			$mockBlock = $this->createBlockMock( $blockType );
		}

		$mockUser = $this->createMock( User::class );
		$mockUser
			->method( 'isSystemUser' )
			->willReturn( false );
		$mockUser
			->method( 'getBlock' )
			->willReturn( $mockBlock );
		$mockUser
			->method( 'isAllowed' )
			->willReturnCallback(
				static fn ( string $permission ) => $permission === 'skipcaptcha' &&
					$userCanSkipCaptcha
			);

		$context->setUser( $mockUser );

		$out = $context->getOutput();
		$out->setTitle( $title );

		$objectUnderTest = new BeforePageDisplayHookHandler(
			$this->getServiceContainer()->getMainConfig(),
			$this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' ),
			$this->getServiceContainer()->get( 'ConfirmEditHCaptchaBlocksLookup' ),
			$this->getServiceContainer()->getHookContainer(),
		);
		$objectUnderTest->onBeforePageDisplay(
			$out,
			$this->createMock( Skin::class )
		);

		if ( $expectedModulesAdded ) {
			$vars = $out->getJsConfigVars();

			$this->assertContains(
				'ext.confirmEdit.hCaptcha',
				$out->getModules()
			);
			$this->assertSame(
				$expectedSiteKey,
				$vars['wgHCaptchaBlockedIpEditingScoreCollectionSiteKey']
			);
		} else {
			$this->assertNotContains(
				'ext.confirmEdit.hCaptcha',
				$out->getModules()
			);
			$this->assertArrayNotHasKey(
				'wgHCaptchaBlockedIpEditingScoreCollectionSiteKey',
				$out->getJsConfigVars()
			);
		}
	}

	public static function provideOnBeforePageDisplay(): iterable {
		yield 'The action is not an edit' => [
			'expectedModulesAdded' => false,
			'expectedSiteKey' => null,
			'action' => 'view',
			'pageExists' => true,
			'editCaptchaClass' => 'HCaptcha',
			'createCaptchaClass' => 'HCaptcha',
			'passiveModeSiteKey' => null,
			'userCanSkipCaptcha' => false,
			'blockType' => 'ip',
		];

		yield 'The captcha class is not hCaptcha' => [
			'expectedModulesAdded' => false,
			'expectedSiteKey' => null,
			'action' => 'edit',
			'pageExists' => false,
			'editCaptchaClass' => 'SimpleCaptcha',
			'createCaptchaClass' => 'SimpleCaptcha',
			'passiveModeSiteKey' => null,
			'userCanSkipCaptcha' => false,
			'blockType' => 'ip',
		];

		yield 'The user can skip captchas' => [
			'expectedModulesAdded' => false,
			'expectedSiteKey' => null,
			'action' => 'edit',
			'pageExists' => false,
			'editCaptchaClass' => 'HCaptcha',
			'createCaptchaClass' => 'HCaptcha',
			'passiveModeSiteKey' => null,
			'userCanSkipCaptcha' => true,
			'blockType' => 'ip',
		];

		yield 'The user is not blocked' => [
			'expectedModulesAdded' => false,
			'expectedSiteKey' => null,
			'action' => 'edit',
			'pageExists' => false,
			'editCaptchaClass' => 'HCaptcha',
			'createCaptchaClass' => 'HCaptcha',
			'passiveModeSiteKey' => null,
			'userCanSkipCaptcha' => false,
			'blockType' => null,
		];

		yield 'The block does not apply to the current title' => [
			'expectedModulesAdded' => false,
			'expectedSiteKey' => null,
			'action' => 'edit',
			'pageExists' => false,
			'editCaptchaClass' => 'HCaptcha',
			'createCaptchaClass' => 'HCaptcha',
			'passiveModeSiteKey' => null,
			'userCanSkipCaptcha' => false,
			'blockType' => 'ip_partial',
		];

		yield 'The block type is neither an IP or IP range block' => [
			'expectedModulesAdded' => false,
			'expectedSiteKey' => null,
			'action' => 'edit',
			'pageExists' => false,
			'editCaptchaClass' => 'HCaptcha',
			'createCaptchaClass' => 'HCaptcha',
			'passiveModeSiteKey' => null,
			'userCanSkipCaptcha' => false,
			'blockType' => 'user',
		];

		yield 'A composite block with no IP block child' => [
			'expectedModulesAdded' => false,
			'expectedSiteKey' => null,
			'action' => 'edit',
			'pageExists' => false,
			'editCaptchaClass' => 'HCaptcha',
			'createCaptchaClass' => 'HCaptcha',
			'passiveModeSiteKey' => null,
			'userCanSkipCaptcha' => false,
			'blockType' => 'composite_user',
		];

		yield 'There is an IP block but no passive site key is configured' => [
			'expectedModulesAdded' => false,
			'expectedSiteKey' => null,
			'action' => 'edit',
			'pageExists' => false,
			'editCaptchaClass' => 'HCaptcha',
			'createCaptchaClass' => 'HCaptcha',
			'passiveModeSiteKey' => null,
			'userCanSkipCaptcha' => false,
			'blockType' => 'ip',
		];

		yield 'IP block, a global passive mode key is set' => [
			'expectedModulesAdded' => true,
			'expectedSiteKey' => 'passive-mode-global-key',
			'action' => 'edit',
			'pageExists' => false,
			'editCaptchaClass' => 'HCaptcha',
			'createCaptchaClass' => 'HCaptcha',
			'passiveModeSiteKey' => 'passive-mode-global-key',
			'userCanSkipCaptcha' => false,
			'blockType' => 'ip',
		];

		yield 'A composite block with an IP block child emits config' => [
			'expectedModulesAdded' => true,
			'expectedSiteKey' => 'passive-mode-global-key',
			'action' => 'edit',
			'pageExists' => false,
			'editCaptchaClass' => 'HCaptcha',
			'createCaptchaClass' => 'HCaptcha',
			'passiveModeSiteKey' => 'passive-mode-global-key',
			'userCanSkipCaptcha' => false,
			'blockType' => 'composite_ip',
		];

		yield '"create" captcha instance is used for non-existing pages' => [
			'expectedModulesAdded' => true,
			'expectedSiteKey' => 'passive-mode-global-key',
			'action' => 'edit',
			'pageExists' => false,
			'editCaptchaClass' => 'SimpleCaptcha',
			'createCaptchaClass' => 'HCaptcha',
			'passiveModeSiteKey' => 'passive-mode-global-key',
			'userCanSkipCaptcha' => false,
			'blockType' => 'ip',
		];

		yield '"create" captcha instance is not used for existing pages' => [
			'expectedModulesAdded' => false,
			'expectedSiteKey' => null,
			'action' => 'edit',
			'pageExists' => true,
			'editCaptchaClass' => 'SimpleCaptcha',
			'createCaptchaClass' => 'HCaptcha',
			'passiveModeSiteKey' => 'passive-mode-global-key',
			'userCanSkipCaptcha' => false,
			'blockType' => 'ip',
		];

		yield 'IP block with action=submit loads modules' => [
			'expectedModulesAdded' => true,
			'expectedSiteKey' => 'passive-mode-global-key',
			'action' => 'submit',
			'pageExists' => false,
			'editCaptchaClass' => 'HCaptcha',
			'createCaptchaClass' => 'HCaptcha',
			'passiveModeSiteKey' => 'passive-mode-global-key',
			'userCanSkipCaptcha' => false,
			'blockType' => 'ip',
		];

		yield '"edit" captcha instance is used for existing pages' => [
			'expectedModulesAdded' => true,
			'expectedSiteKey' => 'passive-mode-global-key',
			'action' => 'edit',
			'pageExists' => true,
			'editCaptchaClass' => 'HCaptcha',
			'createCaptchaClass' => 'SimpleCaptcha',
			'passiveModeSiteKey' => 'passive-mode-global-key',
			'userCanSkipCaptcha' => false,
			'blockType' => 'ip',
		];

		yield 'IP block, but the User-Agent matches a crawler skip pattern' => [
			'expectedModulesAdded' => false,
			'expectedSiteKey' => null,
			'action' => 'edit',
			'pageExists' => false,
			'editCaptchaClass' => 'HCaptcha',
			'createCaptchaClass' => 'HCaptcha',
			'passiveModeSiteKey' => 'passive-mode-global-key',
			'userCanSkipCaptcha' => false,
			'blockType' => 'ip',
			'skipUserAgents' => [ '/ExampleBot/i', '#other-crawler#' ],
			'userAgent' => 'ExampleBot/1.0 (+https://example.com/bot)',
		];

		yield 'IP block with a User-Agent that matches no skip pattern' => [
			'expectedModulesAdded' => true,
			'expectedSiteKey' => 'passive-mode-global-key',
			'action' => 'edit',
			'pageExists' => false,
			'editCaptchaClass' => 'HCaptcha',
			'createCaptchaClass' => 'HCaptcha',
			'passiveModeSiteKey' => 'passive-mode-global-key',
			'userCanSkipCaptcha' => false,
			'blockType' => 'ip',
			'skipUserAgents' => [ '/ExampleBot/i' ],
			'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
		];

		yield 'IP block with skip patterns configured but no User-Agent header' => [
			'expectedModulesAdded' => true,
			'expectedSiteKey' => 'passive-mode-global-key',
			'action' => 'edit',
			'pageExists' => false,
			'editCaptchaClass' => 'HCaptcha',
			'createCaptchaClass' => 'HCaptcha',
			'passiveModeSiteKey' => 'passive-mode-global-key',
			'userCanSkipCaptcha' => false,
			'blockType' => 'ip',
			'skipUserAgents' => [ '/ExampleBot/i' ],
			'userAgent' => null,
		];
	}

	/**
	 * Creates a mock block for use in testOnBeforePageDisplay.
	 *
	 * Accepted $setup values:
	 * - 'ip'             Site-wide IP block (AnonIpBlockTarget)
	 * - 'user'           User block (no IP target)
	 * - 'ip_partial'     IP block that does not apply to the current title
	 * - 'composite_ip'   CompositeBlock whose child is a site-wide IP block
	 * - 'composite_user' CompositeBlock whose child is a user block (no IP)
	 */
	private function createBlockMock( string $setup ): Block {
		return match ( $setup ) {
			'ip' => $this->createBlockMockWithTarget(
				true,
				$this->createMock( AnonIpBlockTarget::class )
			),
			'user' => $this->createBlockMockWithTarget(
				true,
				null
			),
			'ip_partial' => $this->createBlockMockWithTarget(
				false,
				$this->createMock( AnonIpBlockTarget::class )
			),
			'composite_ip' => $this->createCompositeBlockMockWithTarget(
				true,
				null,
				[
					$this->createBlockMockWithTarget(
						true,
						$this->createMock( AnonIpBlockTarget::class )
					)
				]
			),
			'composite_user' => $this->createCompositeBlockMockWithTarget(
				true,
				null,
				[ $this->createBlockMockWithTarget( true, null ) ]
			),
			default => throw new InvalidArgumentException( "Unknown setup: $setup" ),
		};
	}

	/**
	 * @dataProvider provideAccountCreationRiskScoreNoOp
	 */
	public function testMaybeCollectAccountCreationRiskScoreNoOp(
		bool $useRiskScore,
		bool $wasPosted,
		string $createAccountCaptchaClass,
		bool $hasToken,
		bool $hasBlock
	): void {
		$this->configureAccountCreationCaptcha( $useRiskScore, $createAccountCaptchaClass );

		$block = $hasBlock
			? $this->createBlockMockWithTarget( true, $this->createMock( AnonIpBlockTarget::class ) )
			: null;

		$captured = $this->runAccountCreationHandlerCapturingHook(
			new FauxRequest( $hasToken ? [ 'h-captcha-response' => 'abcdef' ] : [], $wasPosted ),
			$block
		);

		$this->assertNull( $captured );
	}

	public static function provideAccountCreationRiskScoreNoOp(): iterable {
		yield 'Request was not posted' => [ true, false, 'HCaptcha', true, true ];
		yield 'Risk score collection disabled' => [ false, true, 'HCaptcha', true, true ];
		yield 'Captcha class is not hCaptcha' => [ true, true, 'SimpleCaptcha', true, true ];
		yield 'No token in the request' => [ true, true, 'HCaptcha', false, true ];
		yield 'No applicable block' => [ true, true, 'HCaptcha', true, false ];
	}

	public function testMaybeCollectAccountCreationRiskScoreFiresHook(): void {
		$this->configureAccountCreationCaptcha( true, 'HCaptcha' );
		$this->overrideConfigValue( 'HCaptchaSecretKey', 'secret-key' );

		$httpRequest = $this->createMock( MWHttpRequest::class );
		$httpRequest->method( 'execute' )->willReturn( Status::newGood() );
		$httpRequest->method( 'getContent' )->willReturn( FormatJson::encode( [
			'success' => true,
			'sitekey' => 'create-account-site-key',
			'score' => 0.5,
		] ) );
		$this->installMockHttp( $httpRequest );

		$captured = $this->runAccountCreationHandlerCapturingHook(
			new FauxRequest( [ 'h-captcha-response' => 'abcdef' ], true ),
			$this->createBlockMockWithTarget( true, $this->createMock( AnonIpBlockTarget::class ) )
		);

		$this->assertNotNull( $captured );
		$this->assertSame( 0.5, $captured['riskScore'] );
		$this->assertSame( [ 123 ], $captured['localBlockIds'] );
		$this->assertSame( [], $captured['globalBlockIds'] );
	}

	private function configureAccountCreationCaptcha(
		bool $useRiskScore,
		string $captchaClass
	): void {
		$this->overrideConfigValue( 'HCaptchaUseRiskScore', $useRiskScore );
		$this->overrideConfigValue( 'CaptchaTriggers', [
			CaptchaTriggers::CREATE_ACCOUNT => [
				'trigger' => true,
				'class' => $captchaClass,
				'config' => [ 'HCaptchaSiteKey' => 'create-account-site-key' ],
			],
		] );
		self::clearCaptchaFactoryGlobalInstances();
	}

	/**
	 * Runs onBeforePageDisplay for a Special:CreateAccount request and returns the
	 * arguments passed to the ConfirmEditHCaptchaRiskScoreRetrievedForBlocks hook,
	 * or null if the hook was not fired.
	 *
	 * @return array{riskScore: float, localBlockIds: int[], globalBlockIds: int[]}|null
	 */
	private function runAccountCreationHandlerCapturingHook(
		FauxRequest $request,
		?Block $block
	): ?array {
		$captured = null;
		$this->setTemporaryHook(
			'ConfirmEditHCaptchaRiskScoreRetrievedForBlocks',
			static function (
				float $riskScore,
				array $localBlockIds,
				array $globalBlockIds
			) use ( &$captured ) {
				$captured = [
					'riskScore' => $riskScore,
					'localBlockIds' => $localBlockIds,
					'globalBlockIds' => $globalBlockIds,
				];
			}
		);

		$context = RequestContext::getMain();
		$context->setRequest( $request );

		$mockUser = $this->createMock( User::class );
		$mockUser->method( 'getName' )->willReturn( '1.2.3.4' );
		$mockUser->method( 'getBlock' )->willReturn( $block );
		$context->setUser( $mockUser );

		$out = $context->getOutput();
		$out->setTitle( Title::newFromText( 'Special:CreateAccount' ) );

		$handler = new BeforePageDisplayHookHandler(
			$this->getServiceContainer()->getMainConfig(),
			$this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' ),
			$this->getServiceContainer()->get( 'ConfirmEditHCaptchaBlocksLookup' ),
			$this->getServiceContainer()->getHookContainer(),
		);
		$handler->onBeforePageDisplay( $out, $this->createMock( Skin::class ) );
		DeferredUpdates::doUpdates();

		return $captured;
	}

	/**
	 * @dataProvider provideCreateAccountBlocks
	 */
	public function testGetCreateAccountBlocksRequiringHCaptcha(
		string $setup,
		array $expectedLocalBlockIds,
		array $expectedGlobalBlockIds
	): void {
		if ( in_array( $setup, [ 'global', 'composite_mixed' ], true ) ) {
			$this->markTestSkippedIfExtensionNotLoaded( 'GlobalBlocking' );
		}

		$block = $this->makeCreateAccountBlock( $setup );

		$handler = new BeforePageDisplayHookHandler(
			$this->getServiceContainer()->getMainConfig(),
			$this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' ),
			$this->getServiceContainer()->get( 'ConfirmEditHCaptchaBlocksLookup' ),
			$this->getServiceContainer()->getHookContainer(),
		);

		$result = TestingAccessWrapper::newFromObject( $handler )
			->getCreateAccountBlocksRequiringHCaptcha( $block );

		$this->assertSame(
			$expectedLocalBlockIds,
			array_map( static fn ( Block $b ) => $b->getId(), $result['local'] )
		);
		$this->assertSame(
			$expectedGlobalBlockIds,
			array_map( static fn ( Block $b ) => $b->getId(), $result['global'] )
		);
	}

	public static function provideCreateAccountBlocks(): iterable {
		yield 'No block' => [ 'none', [], [] ];
		yield 'Local IP block preventing account creation' => [ 'ip_local', [ 123 ], [] ];
		yield 'Local range block preventing account creation' => [ 'range_local', [ 123 ], [] ];
		yield 'IP block that does not prevent account creation' => [ 'ip_no_createaccount', [], [] ];
		yield 'Block without an IP target' => [ 'user_no_ip', [], [] ];
		yield 'Global IP block preventing account creation' => [ 'global', [], [ 124 ] ];
		yield 'Composite with local and global IP children' => [ 'composite_mixed', [ 123 ], [ 124 ] ];
		yield 'Composite whose only child has no IP target' => [ 'composite_no_ip', [], [] ];
	}

	private function makeCreateAccountBlock( string $setup ): ?Block {
		return match ( $setup ) {
			'none' => null,
			'ip_local' => $this->createBlockMockWithTarget(
				true,
				$this->createMock( AnonIpBlockTarget::class )
			),
			'range_local' => $this->createBlockMockWithTarget(
				true,
				$this->createMock( RangeBlockTarget::class )
			),
			'ip_no_createaccount' => $this->createBlockMockWithTarget(
				false,
				$this->createMock( AnonIpBlockTarget::class )
			),
			'user_no_ip' => $this->createBlockMockWithTarget(
				true,
				null
			),
			'global' => $this->createBlockMockWithTarget(
				true,
				$this->createMock( AnonIpBlockTarget::class ),
				GlobalBlock::class,
				124
			),
			'composite_mixed' => $this->createCompositeBlockMockWithTarget(
				true,
				null,
				[
					$this->createBlockMockWithTarget(
						true,
						$this->createMock( AnonIpBlockTarget::class )
					),
					$this->createBlockMockWithTarget(
						true,
						$this->createMock( AnonIpBlockTarget::class ),
						GlobalBlock::class,
						124
					),
				]
			),
			'composite_no_ip' => $this->createCompositeBlockMockWithTarget(
				true,
				null,
				[ $this->createBlockMockWithTarget( true, null ) ]
			),
			default => throw new InvalidArgumentException( "Unknown setup: $setup" ),
		};
	}
}
