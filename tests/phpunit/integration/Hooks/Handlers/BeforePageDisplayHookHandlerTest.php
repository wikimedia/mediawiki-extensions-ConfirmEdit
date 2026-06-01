<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration\Hooks\Handlers;

use InvalidArgumentException;
use MediaWiki\Block\AbstractBlock;
use MediaWiki\Block\AnonIpBlockTarget;
use MediaWiki\Block\Block;
use MediaWiki\Block\BlockTarget;
use MediaWiki\Block\CompositeBlock;
use MediaWiki\Block\RangeBlockTarget;
use MediaWiki\Block\SystemBlock;
use MediaWiki\Context\RequestContext;
use MediaWiki\Deferred\DeferredUpdates;
use MediaWiki\Extension\ConfirmEdit\CaptchaTriggers;
use MediaWiki\Extension\ConfirmEdit\Hooks\Handlers\BeforePageDisplayHookHandler;
use MediaWiki\Extension\ConfirmEdit\Tests\Integration\CaptchaTestHelperTrait;
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
use PHPUnit\Framework\MockObject\MockObject;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\Hooks\Handlers\BeforePageDisplayHookHandler
 * @group Database
 */
class BeforePageDisplayHookHandlerTest extends MediaWikiIntegrationTestCase {
	use CaptchaTestHelperTrait;
	use MockHttpTrait;

	protected function tearDown(): void {
		self::clearCaptchaFactoryGlobalInstances();

		parent::tearDown();
	}

	/** @dataProvider provideOnBeforePageDisplay */
	public function testOnBeforePageDisplay(
		bool $expectedModulesAdded,
		?string $expectedSiteKey,
		array $expectedLocalBlockIds,
		array $expectedGlobalBlockIds,
		string $action,
		bool $pageExists,
		string $editCaptchaClass,
		string $createCaptchaClass,
		?string $passiveModeSiteKey,
		bool $userCanSkipCaptcha,
		?string $blockType
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

		$context = RequestContext::getMain();
		$context->setRequest(
			new FauxRequest( [ 'action' => $action ] )
		);

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
			$this->getServiceContainer()->getHookContainer(),
		);
		$objectUnderTest->onBeforePageDisplay(
			$out,
			$this->createMock( Skin::class )
		);

		if ( $expectedModulesAdded ) {
			$vars = $out->getJsConfigVars();

			$this->assertArrayHasKey(
				'wgHCaptchaBlockedIpEditingScoreCollectionConfig',
				$vars
			);
			$this->assertContains(
				'ext.confirmEdit.hCaptcha',
				$out->getModules()
			);

			$config = $vars['wgHCaptchaBlockedIpEditingScoreCollectionConfig'];
			$this->assertSame( $expectedSiteKey, $config['siteKey'] );
			$this->assertSame( $expectedLocalBlockIds, $config['localBlockIds'] );
			$this->assertSame( $expectedGlobalBlockIds, $config['globalBlockIds'] );
		} else {
			$this->assertNotContains(
				'ext.confirmEdit.hCaptcha',
				$out->getModules()
			);
			$this->assertArrayNotHasKey(
				'wgHCaptchaBlockedIpEditingScoreCollectionConfig',
				$out->getJsConfigVars()
			);
		}
	}

	public static function provideOnBeforePageDisplay(): iterable {
		yield 'The action is not an edit' => [
			'expectedModulesAdded' => false,
			'expectedSiteKey' => null,
			'expectedLocalBlockIds' => [],
			'expectedGlobalBlockIds' => [],
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
			'expectedLocalBlockIds' => [],
			'expectedGlobalBlockIds' => [],
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
			'expectedLocalBlockIds' => [],
			'expectedGlobalBlockIds' => [],
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
			'expectedLocalBlockIds' => [],
			'expectedGlobalBlockIds' => [],
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
			'expectedLocalBlockIds' => [],
			'expectedGlobalBlockIds' => [],
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
			'expectedLocalBlockIds' => [],
			'expectedGlobalBlockIds' => [],
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
			'expectedLocalBlockIds' => [],
			'expectedGlobalBlockIds' => [],
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
			'expectedLocalBlockIds' => [],
			'expectedGlobalBlockIds' => [],
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
			'expectedLocalBlockIds' => [ 123 ],
			'expectedGlobalBlockIds' => [],
			'action' => 'edit',
			'pageExists' => false,
			'editCaptchaClass' => 'HCaptcha',
			'createCaptchaClass' => 'HCaptcha',
			'passiveModeSiteKey' => 'passive-mode-global-key',
			'userCanSkipCaptcha' => false,
			'blockType' => 'ip',
		];

		yield 'Range block, a global passive mode key is set' => [
			'expectedModulesAdded' => true,
			'expectedSiteKey' => 'passive-mode-global-key',
			'expectedLocalBlockIds' => [ 123 ],
			'expectedGlobalBlockIds' => [],
			'action' => 'edit',
			'pageExists' => false,
			'editCaptchaClass' => 'HCaptcha',
			'createCaptchaClass' => 'HCaptcha',
			'passiveModeSiteKey' => 'passive-mode-global-key',
			'userCanSkipCaptcha' => false,
			'blockType' => 'range',
		];

		yield 'A composite block with an IP block child' => [
			'expectedModulesAdded' => true,
			'expectedSiteKey' => 'passive-mode-global-key',
			'expectedLocalBlockIds' => [ 123 ],
			'expectedGlobalBlockIds' => [],
			'action' => 'edit',
			'pageExists' => false,
			'editCaptchaClass' => 'HCaptcha',
			'createCaptchaClass' => 'HCaptcha',
			'passiveModeSiteKey' => 'passive-mode-global-key',
			'userCanSkipCaptcha' => false,
			'blockType' => 'composite_ip',
		];

		yield 'Composite block with a SystemBlock IP child' => [
			'expectedModulesAdded' => true,
			'expectedSiteKey' => 'passive-mode-global-key',
			'expectedLocalBlockIds' => [ 123 ],
			'expectedGlobalBlockIds' => [],
			'action' => 'edit',
			'pageExists' => false,
			'editCaptchaClass' => 'HCaptcha',
			'createCaptchaClass' => 'HCaptcha',
			'passiveModeSiteKey' => 'passive-mode-global-key',
			'userCanSkipCaptcha' => false,
			'blockType' => 'composite_system_block',
		];

		yield 'Composite block with a GlobalBlock IP child' => [
			'expectedModulesAdded' => true,
			'expectedSiteKey' => 'passive-mode-global-key',
			'expectedLocalBlockIds' => [],
			'expectedGlobalBlockIds' => [ 123 ],
			'action' => 'edit',
			'pageExists' => false,
			'editCaptchaClass' => 'HCaptcha',
			'createCaptchaClass' => 'HCaptcha',
			'passiveModeSiteKey' => 'passive-mode-global-key',
			'userCanSkipCaptcha' => false,
			'blockType' => 'composite_global_block',
		];

		yield 'SystemBlock with an IP target' => [
			'expectedModulesAdded' => true,
			'expectedSiteKey' => 'passive-mode-global-key',
			'expectedLocalBlockIds' => [ 123 ],
			'expectedGlobalBlockIds' => [],
			'action' => 'edit',
			'pageExists' => false,
			'editCaptchaClass' => 'HCaptcha',
			'createCaptchaClass' => 'HCaptcha',
			'passiveModeSiteKey' => 'passive-mode-global-key',
			'userCanSkipCaptcha' => false,
			'blockType' => 'system_block',
		];

		yield 'GlobalBlock with an IP target' => [
			'expectedModulesAdded' => true,
			'expectedSiteKey' => 'passive-mode-global-key',
			'expectedLocalBlockIds' => [],
			'expectedGlobalBlockIds' => [ 123 ],
			'action' => 'edit',
			'pageExists' => false,
			'editCaptchaClass' => 'HCaptcha',
			'createCaptchaClass' => 'HCaptcha',
			'passiveModeSiteKey' => 'passive-mode-global-key',
			'userCanSkipCaptcha' => false,
			'blockType' => 'global_block',
		];

		yield 'Partial GlobalBlock that does not apply to the current page' => [
			'expectedModulesAdded' => false,
			'expectedSiteKey' => null,
			'expectedLocalBlockIds' => [],
			'expectedGlobalBlockIds' => [],
			'action' => 'edit',
			'pageExists' => false,
			'editCaptchaClass' => 'HCaptcha',
			'createCaptchaClass' => 'HCaptcha',
			'passiveModeSiteKey' => 'passive-mode-global-key',
			'userCanSkipCaptcha' => false,
			'blockType' => 'global_block_partial',
		];

		yield '"create" captcha instance is used for non-existing pages' => [
			'expectedModulesAdded' => true,
			'expectedSiteKey' => 'passive-mode-global-key',
			'expectedLocalBlockIds' => [ 123 ],
			'expectedGlobalBlockIds' => [],
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
			'expectedLocalBlockIds' => [],
			'expectedGlobalBlockIds' => [],
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
			'expectedLocalBlockIds' => [ 123 ],
			'expectedGlobalBlockIds' => [],
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
			'expectedLocalBlockIds' => [ 123 ],
			'expectedGlobalBlockIds' => [],
			'action' => 'edit',
			'pageExists' => true,
			'editCaptchaClass' => 'HCaptcha',
			'createCaptchaClass' => 'SimpleCaptcha',
			'passiveModeSiteKey' => 'passive-mode-global-key',
			'userCanSkipCaptcha' => false,
			'blockType' => 'ip',
		];

		yield 'A composite block whose own target is an IP (inherited from first child) only returns child IDs' => [
			'expectedModulesAdded' => true,
			'expectedSiteKey' => 'passive-mode-global-key',
			'expectedLocalBlockIds' => [ 123 ],
			'expectedGlobalBlockIds' => [],
			'action' => 'edit',
			'pageExists' => false,
			'editCaptchaClass' => 'HCaptcha',
			'createCaptchaClass' => 'HCaptcha',
			'passiveModeSiteKey' => 'passive-mode-global-key',
			'userCanSkipCaptcha' => false,
			'blockType' => 'composite_ip_with_ip_target',
		];

		yield 'A composite block with multiple IP block children' => [
			'expectedModulesAdded' => true,
			'expectedSiteKey' => 'passive-mode-global-key',
			'expectedLocalBlockIds' => [ 123, 124 ],
			'expectedGlobalBlockIds' => [],
			'action' => 'edit',
			'pageExists' => false,
			'editCaptchaClass' => 'HCaptcha',
			'createCaptchaClass' => 'HCaptcha',
			'passiveModeSiteKey' => 'passive-mode-global-key',
			'userCanSkipCaptcha' => false,
			'blockType' => 'composite_multi_ip',
		];

		yield 'A composite block with both a local and a global IP block child' => [
			'expectedModulesAdded' => true,
			'expectedSiteKey' => 'passive-mode-global-key',
			'expectedLocalBlockIds' => [ 123 ],
			'expectedGlobalBlockIds' => [ 124 ],
			'action' => 'edit',
			'pageExists' => false,
			'editCaptchaClass' => 'HCaptcha',
			'createCaptchaClass' => 'HCaptcha',
			'passiveModeSiteKey' => 'passive-mode-global-key',
			'userCanSkipCaptcha' => false,
			'blockType' => 'composite_mixed',
		];
	}

	 /**
	  * Creates a mock block for use in testOnBeforePageDisplay.
	  *
	  * Accepted $setup values:
	  * - 'ip'             Site-wide IP block (AnonIpBlockTarget)
	  * - 'range'          Site-wide range block (RangeBlockTarget)
	  * - 'user'           User block (no IP target)
	  * - 'ip_partial'     IP block that does not apply to the current title
	  * - 'composite_ip'   CompositeBlock whose child is a site-wide IP block
	  * - 'composite_user' CompositeBlock whose child is a user block (no IP)
	  * - 'system_block'          SystemBlock with an IP target
	  * - 'global_block'          GlobalBlock with an IP target (sitewide)
	  * - 'global_block_partial'  GlobalBlock with an IP target that does not apply to the current page
	  * - 'composite_system_block' CompositeBlock whose child is a SystemBlock
	  *                            with an IP target
	  * - 'composite_global_block' CompositeBlock whose child is a GlobalBlock
	  *                            with an IP target
	  * - 'composite_multi_ip'    CompositeBlock with two local IP blocks
	  * - 'composite_mixed'       CompositeBlock with local and Global IP blocks
	  * - 'composite_ip_with_ip_target' CompositeBlock whose own target is an IP
	  *                            (as in production, inherited from first child),
	  *                            to verify the composite's own ID is not returned
	  */
	private function createBlockMock( string $setup ): Block {
		$mock = match ( $setup ) {
			'ip' => $this->createBlockMockWithTarget(
				true,
				$this->createMock( AnonIpBlockTarget::class )
			),
			'range' => $this->createBlockMockWithTarget(
				true,
				$this->createMock( RangeBlockTarget::class )
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
			'system_block' => $this->createBlockMockWithTarget(
				false,
				$this->createMock( AnonIpBlockTarget::class ),
				SystemBlock::class
			),
			// The child's appliesToTitle returns false to prove it is never
			// consulted for SystemBlock children.
			'composite_system_block' => $this->createCompositeBlockMockWithTarget(
				true,
				null,
				[
					$this->createBlockMockWithTarget(
						false,
						$this->createMock( AnonIpBlockTarget::class ),
						SystemBlock::class
					)
				]
			),
			'composite_ip_with_ip_target' => $this->createCompositeBlockMockWithTarget(
				true,
				$this->createMock( AnonIpBlockTarget::class ),
				[
					$this->createBlockMockWithTarget(
						true,
						$this->createMock( AnonIpBlockTarget::class )
					)
				]
			),
			'composite_multi_ip' => $this->createCompositeBlockMockWithTarget(
				true,
				null,
				[
					$this->createBlockMockWithTarget(
						true,
						$this->createMock( AnonIpBlockTarget::class )
					),
					$this->createBlockMockWithTarget(
						true,
						$this->createMock( RangeBlockTarget::class ),
						AbstractBlock::class,
						124
					),
				]
			),
			default => null,
		};

		if ( $mock ) {
			return $mock;
		}

		// Next block types require GlobalBlocking to be installed
		$this->markTestSkippedIfExtensionNotLoaded( 'GlobalBlocking' );

		return match ( $setup ) {
				'global_block' => $this->createBlockMockWithTarget(
				true,
				$this->createMock( AnonIpBlockTarget::class ),
				GlobalBlock::class
			),
			'global_block_partial' => $this->createBlockMockWithTarget(
				false,
				$this->createMock( AnonIpBlockTarget::class ),
				GlobalBlock::class
			),
			'composite_global_block' => $this->createCompositeBlockMockWithTarget(
				true,
				null,
				[
					$this->createBlockMockWithTarget(
						true,
						$this->createMock( AnonIpBlockTarget::class ),
						GlobalBlock::class
					)
				]
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
			default => throw new InvalidArgumentException(
				"Unknown setup: $setup"
			)
		};
	}

	/**
	 * @return (MockObject&AbstractBlock)
	 */
	private function createBlockMockWithTarget(
		bool $appliesToTitle,
		?BlockTarget $target,
		string $originalClassName = AbstractBlock::class,
		int $id = 123
	): object {
		$block = $this->createMock( $originalClassName );
		$block
			->method( 'getId' )
			->willReturn( $id );
		$block
			->method( 'appliesToTitle' )
			->willReturn( $appliesToTitle );
		// The same flag gates the createaccount-right check used by the
		// account creation block tests.
		$block
			->method( 'appliesToRight' )
			->willReturn( $appliesToTitle );
		$block
			->method( 'getTarget' )
			->willReturn( $target );

		return $block;
	}

	private function createCompositeBlockMockWithTarget(
		bool $appliesToTitle,
		?BlockTarget $target,
		array $children
	): object {
		$composite = $this->createBlockMockWithTarget(
			$appliesToTitle,
			$target,
			CompositeBlock::class,
			456
		);

		$composite
			->method( 'getOriginalBlocks' )
			->willReturn( $children );

		return $composite;
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
