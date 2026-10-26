<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration\Hooks\Handlers;

use InvalidArgumentException;
use MediaWiki\Block\AnonIpBlockTarget;
use MediaWiki\Block\Block;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\ConfirmEdit\CaptchaTriggers;
use MediaWiki\Extension\ConfirmEdit\Hooks\Handlers\BeforePageDisplayHookHandler;
use MediaWiki\Extension\ConfirmEdit\Tests\Integration\CaptchaTestHelperTrait;
use MediaWiki\Extension\ConfirmEdit\Tests\Integration\HCaptchaBlockMockTrait;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Skin\Skin;
use MediaWiki\User\User;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\Hooks\Handlers\BeforePageDisplayHookHandler
 * @group Database
 */
class BeforePageDisplayHookHandlerTest extends MediaWikiIntegrationTestCase {
	use CaptchaTestHelperTrait;
	use HCaptchaBlockMockTrait;

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
			$this->getServiceContainer()->get( 'ConfirmEditHCaptchaRiskScoreCrawlerFilter' ),
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
}
