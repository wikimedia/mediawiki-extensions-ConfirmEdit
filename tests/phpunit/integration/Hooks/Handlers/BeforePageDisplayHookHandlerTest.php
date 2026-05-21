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
use MediaWiki\Extension\ConfirmEdit\CaptchaTriggers;
use MediaWiki\Extension\ConfirmEdit\Hooks\Handlers\BeforePageDisplayHookHandler;
use MediaWiki\Extension\ConfirmEdit\Tests\Integration\CaptchaTestHelperTrait;
use MediaWiki\Extension\GlobalBlocking\GlobalBlock;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Skin\Skin;
use MediaWiki\User\User;
use MediaWikiIntegrationTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\Hooks\Handlers\BeforePageDisplayHookHandler
 * @group Database
 */
class BeforePageDisplayHookHandlerTest extends MediaWikiIntegrationTestCase {
	use CaptchaTestHelperTrait;

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
			$this->getServiceContainer()->getMainConfig()
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
	  * - 'global_block'          GlobalBlock with an IP target
	  * - 'composite_system_block' CompositeBlock whose child is a SystemBlock
	  *                            with an IP target
	  * - 'composite_global_block' CompositeBlock whose child is a GlobalBlock
	  *                            with an IP target
	  * - 'composite_multi_ip'    CompositeBlock with two local IP blocks
	  * - 'composite_mixed'       CompositeBlock with local and Global IP blocks
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
				false,
				$this->createMock( AnonIpBlockTarget::class ),
				GlobalBlock::class
			),
			// The child's appliesToTitle returns false to prove it is never
			// consulted for GlobalBlock children.
			'composite_global_block' => $this->createCompositeBlockMockWithTarget(
				true,
				null,
				[
					$this->createBlockMockWithTarget(
						false,
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
						false,
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
}
