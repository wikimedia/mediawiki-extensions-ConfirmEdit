<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration\Hooks\Handlers;

use MediaWiki\Block\AbstractBlock;
use MediaWiki\Block\AnonIpBlockTarget;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\ConfirmEdit\CaptchaTriggers;
use MediaWiki\Extension\ConfirmEdit\Hooks\Handlers\MakeGlobalVariablesScriptHookHandler;
use MediaWiki\Extension\ConfirmEdit\Services\CaptchaFactory;
use MediaWiki\Extension\VisualEditor\Services\VisualEditorAvailabilityLookup;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWikiIntegrationTestCase;
use MobileContext;
use Wikimedia\ArrayUtils\ArrayUtils;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\Hooks\Handlers\MakeGlobalVariablesScriptHookHandler
 * @group Database
 */
class MakeGlobalVariablesScriptHookHandlerTest extends MediaWikiIntegrationTestCase {
	/** @dataProvider provideMakeGlobalVariablesScript */
	public function testMakeGlobalVariablesScript( object $testCase ): void {
		$this->markTestSkippedIfExtensionNotLoaded( 'VisualEditor' );
		$this->markTestSkippedIfExtensionNotLoaded( 'MobileFrontend' );

		// Make hCaptcha be used as the captcha for editing, so it will be the captcha specified in the JS config var
		$this->overrideConfigValue( 'CaptchaTriggers', [
			'create' => [
				'trigger' => $testCase->shouldCheckResult,
				'class' => $testCase->createCaptchaClass,
				'config' => [ 'HCaptchaSiteKey' => 'foo', 'HCaptchaAlwaysChallengeSiteKey' => 'foo-always' ]
			],
			'edit' => [
				'trigger' => $testCase->shouldCheckResult,
				'class' => 'HCaptcha',
				'config' => [ 'HCaptchaSiteKey' => 'bar' ]
			],
		] );
		$this->clearHook( 'ConfirmEditCaptchaClass' );

		$out = RequestContext::getMain()->getOutput();
		$out->setTitle( Title::makeTitle( NS_MAIN, 'MakeGlobalVariablesScript' ) );

		if ( $testCase->isVisualEditorAvailable !== null ) {
			$mockVisualEditorAvailabilityLookup = $this->createMock( VisualEditorAvailabilityLookup::class );
			$mockVisualEditorAvailabilityLookup->method( 'isAvailable' )
				->with( $out->getTitle(), $out->getRequest(), $out->getUser() )
				->willReturn( $testCase->isVisualEditorAvailable );
		} else {
			$mockVisualEditorAvailabilityLookup = null;
		}

		if ( $testCase->isMobileFrontendAvailable !== null ) {
			$mockMobileContext = $this->createMock( MobileContext::class );
			$mockMobileContext->method( 'shouldDisplayMobileView' )
				->willReturn( $testCase->isMobileFrontendAvailable );
		} else {
			$mockMobileContext = null;
		}

		$this->overrideConfigValue(
			'HCaptchaEnabledInMobileFrontend',
			$testCase->isHCaptchaEnabledInMobileFrontend
		);

		/**
		 * @var $mockExtensionRegistry \MediaWiki\Registration\ExtensionRegistry
		 */
		$mockExtensionRegistry = $this->createMock( ExtensionRegistry::class );
		$mockExtensionRegistry->method( 'isLoaded' )
			->willReturnCallback( static function ( $name ) use ( $testCase ) {
				if ( $name === 'VisualEditor' ) {
					return $testCase->isVisualEditorAvailable !== null;
				}
				if ( $name === 'MobileFrontend' ) {
					return $testCase->isMobileFrontendAvailable !== null;
				}
				if ( $name === 'Abuse Filter' ) {
					return $testCase->isAbuseFilterAvailable;
				}

				return false;
			} );

		/** @var CaptchaFactory $captchaFactory */
		$captchaFactory = $this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' );
		$simpleCaptcha = $captchaFactory->getGlobalInstance( CaptchaTriggers::CREATE );
		$simpleCaptcha->setForceShowCaptcha( $testCase->shouldForceShowCaptcha );

		// Call the hook and expect that variable is added if $isAvailable is true
		$vars = [];
		$objectUnderTest = new MakeGlobalVariablesScriptHookHandler(
			$mockExtensionRegistry,
			$this->getServiceContainer()->getMainConfig(),
			$this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' ),
			$this->getServiceContainer()->get( 'ConfirmEditHCaptchaBlocksLookup' ),
			$this->getServiceContainer()->get( 'ConfirmEditHCaptchaRiskScoreCrawlerFilter' ),
			$mockVisualEditorAvailabilityLookup,
			$mockMobileContext
		);
		$objectUnderTest->onMakeGlobalVariablesScript( $vars, $out );

		$expected = [];

		$usingHCaptcha = strtolower( $testCase->createCaptchaClass ) === 'hcaptcha';
		$mobileFrontendPathActive = $testCase->isMobileFrontendAvailable &&
			$testCase->isHCaptchaEnabledInMobileFrontend &&
			$usingHCaptcha;
		$visualEditorPathActive = !$mobileFrontendPathActive &&
			$testCase->isVisualEditorAvailable === true &&
			$usingHCaptcha;

		// wgMobileFrontendSourceEditorInitializeModules is set via the Mobile
		// Frontend path, or via the VE path when MobileFrontend is also available
		// (so the source editor can initialise hCaptcha).
		if ( $mobileFrontendPathActive ||
			( $visualEditorPathActive && $testCase->isMobileFrontendAvailable ) ) {
			$expected['wgMobileFrontendSourceEditorInitializeModules'] = [
				'ext.confirmEdit.hCaptcha'
			];
		}

		if ( $mobileFrontendPathActive || $visualEditorPathActive ) {
			$this->assertContains( 'ext.confirmEdit.hCaptcha', $out->getModules() );
		}

		if ( $testCase->expectedMobileHCaptchaAbuseFilterEnabled ) {
			$expected['wgConfirmEditMobileHCaptchaAbuseFilterEnabled'] = true;
		}

		if ( $testCase->expectedConfirmEditNeededForCaptchaValue !== null ) {
			$expected['wgConfirmEditCaptchaNeededForGenericEdit'] =
				$testCase->expectedConfirmEditNeededForCaptchaValue;
			$expected['wgConfirmEditForceShowCaptcha'] = $testCase->shouldForceShowCaptcha;

			if ( $testCase->expectedConfirmEditNeededForCaptchaValue === 'hcaptcha' ) {
				$expected['wgConfirmEditHCaptchaSiteKey'] = 'foo';
			}
		}
		if ( $testCase->expectedHCaptchaSiteKeyValue !== null ) {
			$expected['wgConfirmEditHCaptchaSiteKey'] = $testCase->expectedHCaptchaSiteKeyValue;
		}

		$this->assertArrayEquals( $expected, $vars, false, true );
	}

	public function testMakeGlobalVariablesScriptUserCanSkipCaptcha(): void {
		$this->markTestSkippedIfExtensionNotLoaded( 'MobileFrontend' );

		$this->overrideConfigValue( 'CaptchaTriggers', [
			'edit' => [
				'trigger' => true,
				'class' => 'HCaptcha',
				'config' => [ 'HCaptchaSiteKey' => 'bar' ]
			],
		] );
		$this->overrideConfigValue( 'HCaptchaEnabledInMobileFrontend', true );
		$this->clearHook( 'ConfirmEditCaptchaClass' );

		$user = $this->getTestUser()->getUser();
		$this->overrideUserPermissions( $user, [ 'skipcaptcha' ] );
		RequestContext::getMain()->setUser( $user );

		$out = RequestContext::getMain()->getOutput();
		$out->setTitle( Title::makeTitle( NS_MAIN, 'MakeGlobalVariablesScript' ) );

		$mockMobileContext = $this->createMock( MobileContext::class );
		$mockMobileContext->expects( $this->once() )
			->method( 'shouldDisplayMobileView' )
			->willReturn( true );

		$mockExtensionRegistry = $this->createMock( ExtensionRegistry::class );
		$mockExtensionRegistry->method( 'isLoaded' )
			->willReturnCallback( static function ( $name ) {
				if ( $name === 'MobileFrontend' || $name === 'Abuse Filter' ) {
					return true;
				}
				return false;
			} );

		$vars = [];
		$objectUnderTest = new MakeGlobalVariablesScriptHookHandler(
			$mockExtensionRegistry,
			$this->getServiceContainer()->getMainConfig(),
			$this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' ),
			$this->getServiceContainer()->get( 'ConfirmEditHCaptchaBlocksLookup' ),
			$this->getServiceContainer()->get( 'ConfirmEditHCaptchaRiskScoreCrawlerFilter' ),
			null,
			$mockMobileContext
		);
		$objectUnderTest->onMakeGlobalVariablesScript( $vars, $out );

		$this->assertNotContains( 'ext.confirmEdit.hCaptcha', $out->getModules() );
		$this->assertArrayNotHasKey( 'wgMobileFrontendSourceEditorInitializeModules', $vars );
		$this->assertArrayNotHasKey( 'wgConfirmEditMobileHCaptchaAbuseFilterEnabled', $vars );
	}

	public function testMakeGlobalVariablesScriptBlockedIpEditingConfigIncludesBlockIds(): void {
		$this->markTestSkippedIfExtensionNotLoaded( 'MobileFrontend' );

		$this->overrideConfigValue( 'CaptchaTriggers', [
			'create' => [
				'trigger' => true,
				'class' => 'HCaptcha',
				'config' => [ 'HCaptchaSiteKey' => 'bar' ]
			],
			'edit' => [
				'trigger' => true,
				'class' => 'HCaptcha',
				'config' => [ 'HCaptchaSiteKey' => 'bar' ]
			],
		] );
		$this->overrideConfigValue( 'HCaptchaEnabledInMobileFrontend', true );
		$this->overrideConfigValue( 'HCaptchaBlockedIpEditingScoreCollectionSiteKey', 'passive-site-key' );
		$this->clearHook( 'ConfirmEditCaptchaClass' );

		$mockBlockTarget = $this->createMock( AnonIpBlockTarget::class );
		$mockBlock = $this->createMock( AbstractBlock::class );
		$mockBlock->method( 'getTarget' )->willReturn( $mockBlockTarget );
		$mockBlock->method( 'appliesToTitle' )->willReturn( true );
		$mockBlock->method( 'getId' )->willReturn( 42 );

		$mockUser = $this->createMock( User::class );
		$mockUser->method( 'isSystemUser' )->willReturn( false );
		$mockUser->method( 'getBlock' )->willReturn( $mockBlock );
		$mockUser->method( 'isAllowed' )->willReturn( false );

		RequestContext::getMain()->setUser( $mockUser );
		$out = RequestContext::getMain()->getOutput();
		$out->setTitle( Title::makeTitle( NS_MAIN, 'MakeGlobalVariablesScript' ) );

		$mockMobileContext = $this->createMock( MobileContext::class );
		$mockMobileContext->method( 'shouldDisplayMobileView' )->willReturn( true );

		$mockExtensionRegistry = $this->createMock( ExtensionRegistry::class );
		$mockExtensionRegistry->method( 'isLoaded' )
			->willReturnCallback( static fn ( $name ) =>
				$name === 'MobileFrontend'
			);

		$vars = [];
		$objectUnderTest = new MakeGlobalVariablesScriptHookHandler(
			$mockExtensionRegistry,
			$this->getServiceContainer()->getMainConfig(),
			$this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' ),
			$this->getServiceContainer()->get( 'ConfirmEditHCaptchaBlocksLookup' ),
			$this->getServiceContainer()->get( 'ConfirmEditHCaptchaRiskScoreCrawlerFilter' ),
			null,
			$mockMobileContext
		);
		$objectUnderTest->onMakeGlobalVariablesScript( $vars, $out );

		$jsConfigVars = $out->getJsConfigVars();
		$this->assertSame(
			'passive-site-key',
			$jsConfigVars['wgHCaptchaBlockedIpEditingScoreCollectionSiteKey']
		);
	}

	public function testMakeGlobalVariablesScriptBlockedIpEditingConfigIncludesBlockIdsForVE(): void {
		$this->markTestSkippedIfExtensionNotLoaded( 'VisualEditor' );

		$this->overrideConfigValue( 'CaptchaTriggers', [
			'create' => [
				'trigger' => true,
				'class' => 'HCaptcha',
				'config' => [ 'HCaptchaSiteKey' => 'bar' ],
			],
			'edit' => [
				'trigger' => true,
				'class' => 'HCaptcha',
				'config' => [ 'HCaptchaSiteKey' => 'bar' ],
			],
		] );
		$this->overrideConfigValue( 'HCaptchaBlockedIpEditingScoreCollectionSiteKey', 'passive-site-key' );
		$this->clearHook( 'ConfirmEditCaptchaClass' );

		$mockBlockTarget = $this->createMock( AnonIpBlockTarget::class );
		$mockBlock = $this->createMock( AbstractBlock::class );
		$mockBlock->method( 'getTarget' )->willReturn( $mockBlockTarget );
		$mockBlock->method( 'appliesToTitle' )->willReturn( true );
		$mockBlock->method( 'getId' )->willReturn( 42 );

		$mockUser = $this->createMock( User::class );
		$mockUser->method( 'isSystemUser' )->willReturn( false );
		$mockUser->method( 'getBlock' )->willReturn( $mockBlock );
		$mockUser->method( 'isAllowed' )->willReturn( false );

		RequestContext::getMain()->setUser( $mockUser );
		$out = RequestContext::getMain()->getOutput();
		$out->setTitle( Title::makeTitle( NS_MAIN, 'MakeGlobalVariablesScript' ) );

		$mockVisualEditorAvailabilityLookup = $this->createMock( VisualEditorAvailabilityLookup::class );
		$mockVisualEditorAvailabilityLookup->method( 'isAvailable' )
			->with( $out->getTitle(), $out->getRequest(), $out->getUser() )
			->willReturn( true );

		$mockExtensionRegistry = $this->createMock( ExtensionRegistry::class );
		$mockExtensionRegistry->method( 'isLoaded' )
			->willReturnCallback( static fn ( $name ) => $name === 'VisualEditor' );

		$vars = [];
		$objectUnderTest = new MakeGlobalVariablesScriptHookHandler(
			$mockExtensionRegistry,
			$this->getServiceContainer()->getMainConfig(),
			$this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' ),
			$this->getServiceContainer()->get( 'ConfirmEditHCaptchaBlocksLookup' ),
			$this->getServiceContainer()->get( 'ConfirmEditHCaptchaRiskScoreCrawlerFilter' ),
			$mockVisualEditorAvailabilityLookup,
			null
		);
		$objectUnderTest->onMakeGlobalVariablesScript( $vars, $out );

		$jsConfigVars = $out->getJsConfigVars();
		$this->assertSame(
			'passive-site-key',
			$jsConfigVars['wgHCaptchaBlockedIpEditingScoreCollectionSiteKey']
		);
		$this->assertContains( 'ext.confirmEdit.hCaptcha', $out->getModules() );
	}

	public function testMakeGlobalVariablesScriptBlockedIpEditingConfigAbsentForCrawlerUserAgent(): void {
		$this->markTestSkippedIfExtensionNotLoaded( 'VisualEditor' );

		$this->overrideConfigValue( 'CaptchaTriggers', [
			'create' => [
				'trigger' => true,
				'class' => 'HCaptcha',
				'config' => [ 'HCaptchaSiteKey' => 'bar' ],
			],
			'edit' => [
				'trigger' => true,
				'class' => 'HCaptcha',
				'config' => [ 'HCaptchaSiteKey' => 'bar' ],
			],
		] );
		$this->overrideConfigValue( 'HCaptchaBlockedIpEditingScoreCollectionSiteKey', 'passive-site-key' );
		$this->overrideConfigValue(
			'HCaptchaBlockedIpEditingScoreSkipUserAgents',
			[ '#ExampleBot#i' ]
		);
		$this->clearHook( 'ConfirmEditCaptchaClass' );

		$request = new FauxRequest();
		$request->setHeader( 'User-Agent', 'ExampleBot/1.0 (+https://example.com/bot)' );
		RequestContext::getMain()->setRequest( $request );

		$mockBlockTarget = $this->createMock( AnonIpBlockTarget::class );
		$mockBlock = $this->createMock( AbstractBlock::class );
		$mockBlock->method( 'getTarget' )->willReturn( $mockBlockTarget );
		$mockBlock->method( 'appliesToTitle' )->willReturn( true );
		$mockBlock->method( 'getId' )->willReturn( 42 );

		$mockUser = $this->createMock( User::class );
		$mockUser->method( 'isSystemUser' )->willReturn( false );
		$mockUser->method( 'getBlock' )->willReturn( $mockBlock );
		$mockUser->method( 'isAllowed' )->willReturn( false );

		RequestContext::getMain()->setUser( $mockUser );
		$out = RequestContext::getMain()->getOutput();
		$out->setTitle( Title::makeTitle( NS_MAIN, 'MakeGlobalVariablesScript' ) );

		$mockVisualEditorAvailabilityLookup = $this->createMock( VisualEditorAvailabilityLookup::class );
		$mockVisualEditorAvailabilityLookup->method( 'isAvailable' )
			->willReturn( true );

		$mockExtensionRegistry = $this->createMock( ExtensionRegistry::class );
		$mockExtensionRegistry->method( 'isLoaded' )
			->willReturnCallback( static fn ( $name ) => $name === 'VisualEditor' );

		$vars = [];
		$objectUnderTest = new MakeGlobalVariablesScriptHookHandler(
			$mockExtensionRegistry,
			$this->getServiceContainer()->getMainConfig(),
			$this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' ),
			$this->getServiceContainer()->get( 'ConfirmEditHCaptchaBlocksLookup' ),
			$this->getServiceContainer()->get( 'ConfirmEditHCaptchaRiskScoreCrawlerFilter' ),
			$mockVisualEditorAvailabilityLookup,
			null
		);
		$objectUnderTest->onMakeGlobalVariablesScript( $vars, $out );

		$this->assertArrayNotHasKey(
			'wgHCaptchaBlockedIpEditingScoreCollectionSiteKey',
			$out->getJsConfigVars()
		);
		// The functional captcha module still loads; only collection is skipped.
		$this->assertContains( 'ext.confirmEdit.hCaptcha', $out->getModules() );
	}

	public function testMakeGlobalVariablesScriptBlockedIpEditingConfigAbsentWhenNoBlocksForVE(): void {
		$this->markTestSkippedIfExtensionNotLoaded( 'VisualEditor' );

		$this->overrideConfigValue( 'CaptchaTriggers', [
			'create' => [
				'trigger' => true,
				'class' => 'HCaptcha',
				'config' => [ 'HCaptchaSiteKey' => 'bar' ],
			],
			'edit' => [
				'trigger' => true,
				'class' => 'HCaptcha',
				'config' => [ 'HCaptchaSiteKey' => 'bar' ],
			],
		] );
		$this->overrideConfigValue( 'HCaptchaBlockedIpEditingScoreCollectionSiteKey', 'passive-site-key' );
		$this->clearHook( 'ConfirmEditCaptchaClass' );

		$mockUser = $this->createMock( User::class );
		$mockUser->method( 'isSystemUser' )->willReturn( false );
		$mockUser->method( 'getBlock' )->willReturn( null );
		$mockUser->method( 'isAllowed' )->willReturn( false );

		RequestContext::getMain()->setUser( $mockUser );
		$out = RequestContext::getMain()->getOutput();
		$out->setTitle( Title::makeTitle( NS_MAIN, 'MakeGlobalVariablesScript' ) );

		$mockVisualEditorAvailabilityLookup = $this->createMock( VisualEditorAvailabilityLookup::class );
		$mockVisualEditorAvailabilityLookup->method( 'isAvailable' )
			->willReturn( true );

		$mockExtensionRegistry = $this->createMock( ExtensionRegistry::class );
		$mockExtensionRegistry->method( 'isLoaded' )
			->willReturnCallback( static fn ( $name ) => $name === 'VisualEditor' );

		$vars = [];
		$objectUnderTest = new MakeGlobalVariablesScriptHookHandler(
			$mockExtensionRegistry,
			$this->getServiceContainer()->getMainConfig(),
			$this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' ),
			$this->getServiceContainer()->get( 'ConfirmEditHCaptchaBlocksLookup' ),
			$this->getServiceContainer()->get( 'ConfirmEditHCaptchaRiskScoreCrawlerFilter' ),
			$mockVisualEditorAvailabilityLookup,
			null
		);
		$objectUnderTest->onMakeGlobalVariablesScript( $vars, $out );

		$this->assertArrayNotHasKey(
			'wgHCaptchaBlockedIpEditingScoreCollectionSiteKey',
			$out->getJsConfigVars()
		);
	}

	public function testMakeGlobalVariablesScriptBlockedIpEditingConfigAbsentWhenNoBlocks(): void {
		$this->markTestSkippedIfExtensionNotLoaded( 'MobileFrontend' );

		$this->overrideConfigValue( 'CaptchaTriggers', [
			'create' => [
				'trigger' => true,
				'class' => 'HCaptcha',
				'config' => [ 'HCaptchaSiteKey' => 'bar' ],
			],
			'edit' => [
				'trigger' => true,
				'class' => 'HCaptcha',
				'config' => [ 'HCaptchaSiteKey' => 'bar' ],
			],
		] );
		$this->overrideConfigValue( 'HCaptchaEnabledInMobileFrontend', true );
		$this->overrideConfigValue( 'HCaptchaBlockedIpEditingScoreCollectionSiteKey', 'passive-site-key' );
		$this->clearHook( 'ConfirmEditCaptchaClass' );

		$mockUser = $this->createMock( User::class );
		$mockUser->method( 'isSystemUser' )->willReturn( false );
		$mockUser->method( 'getBlock' )->willReturn( null );
		$mockUser->method( 'isAllowed' )->willReturn( false );

		RequestContext::getMain()->setUser( $mockUser );
		$out = RequestContext::getMain()->getOutput();
		$out->setTitle( Title::makeTitle( NS_MAIN, 'MakeGlobalVariablesScript' ) );

		$mockMobileContext = $this->createMock( MobileContext::class );
		$mockMobileContext->method( 'shouldDisplayMobileView' )->willReturn( true );

		$mockExtensionRegistry = $this->createMock( ExtensionRegistry::class );
		$mockExtensionRegistry->method( 'isLoaded' )
			->willReturnCallback( static fn ( $name ) =>
				$name === 'MobileFrontend'
			);

		$vars = [];
		$objectUnderTest = new MakeGlobalVariablesScriptHookHandler(
			$mockExtensionRegistry,
			$this->getServiceContainer()->getMainConfig(),
			$this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' ),
			$this->getServiceContainer()->get( 'ConfirmEditHCaptchaBlocksLookup' ),
			$this->getServiceContainer()->get( 'ConfirmEditHCaptchaRiskScoreCrawlerFilter' ),
			null,
			$mockMobileContext
		);
		$objectUnderTest->onMakeGlobalVariablesScript( $vars, $out );

		$this->assertArrayNotHasKey(
			'wgHCaptchaBlockedIpEditingScoreCollectionSiteKey',
			$out->getJsConfigVars()
		);
	}

	public static function provideMakeGlobalVariablesScript(): iterable {
		$testCases = ArrayUtils::cartesianProduct(
			// VisualEditor availability (null for not installed)
			[ true, false, null ],
			// MobileFrontend editor availability (null for not installed)
			[ true, false, null ],
			// Does the user need to complete a captcha for any edit (a "generic" edit)
			[ true, false ],
			// The value returned by SimpleCaptcha::shouldForceShowCaptcha
			[ true, false ],
			// The captcha class used for create actions
			[ 'HCaptcha', 'SimpleCaptcha' ],
			// Whether the AbuseFilter extension is installed and enabled
			[ true, false ],
			// Whether $wgHCaptchaEnabledInMobileFrontend is enabled
			[ true, false ]
		);

		foreach ( $testCases as $params ) {
			$isVisualEditorAvailable = $params[0];
			$isMobileFrontendAvailable = $params[1];
			$shouldCheck = $params[2];
			$shouldForceShowCaptcha = $params[3];
			$createCaptchaClass = $params[4];
			$isAbuseFilterAvailable = $params[5];
			$isHCaptchaEnabledInMobileFrontend = $params[6];

			$expectedConfirmEditNeededForCaptchaValue = false;
			$expectedHCaptchaSiteKeyValue = null;

			// HCaptchaEnabledInMobileFrontend only affects output when MobileFrontend is available
			if ( $isMobileFrontendAvailable === null && !$isHCaptchaEnabledInMobileFrontend ) {
				continue;
			}

			// The JavaScript config variable will have a value of the captcha class in use if a captcha
			// is needed for a generic edit or SimpleCaptcha::shouldForceShowCaptcha returns true
			if ( $shouldCheck === true || $shouldForceShowCaptcha === true ) {
				$expectedConfirmEditNeededForCaptchaValue = strtolower( $createCaptchaClass );

				if ( $expectedConfirmEditNeededForCaptchaValue === 'hcaptcha' ) {
					$expectedHCaptchaSiteKeyValue = 'foo';
					if ( $shouldForceShowCaptcha === true ) {
						$expectedHCaptchaSiteKeyValue = 'foo-always';
					}
				}
			}

			// ::shouldForceShowCaptcha values only affect the test output if hCaptcha is needed for an edit,
			// so only test with it as false if not hCaptcha
			if ( $expectedConfirmEditNeededForCaptchaValue !== 'hcaptcha' && $shouldForceShowCaptcha === true ) {
				continue;
			}

			$testCase = new class(
				expectedConfirmEditNeededForCaptchaValue:
					$expectedConfirmEditNeededForCaptchaValue,
				expectedHCaptchaSiteKeyValue: $expectedHCaptchaSiteKeyValue,
				expectedMobileHCaptchaAbuseFilterEnabled:
					$isMobileFrontendAvailable && $isAbuseFilterAvailable &&
					$isHCaptchaEnabledInMobileFrontend &&
					strtolower( $createCaptchaClass ) === 'hcaptcha',
				isVisualEditorAvailable: $isVisualEditorAvailable,
				isMobileFrontendAvailable: $isMobileFrontendAvailable,
				isAbuseFilterAvailable: $isAbuseFilterAvailable,
				isHCaptchaEnabledInMobileFrontend: $isHCaptchaEnabledInMobileFrontend,
				shouldCheckResult: $shouldCheck,
				shouldForceShowCaptcha: $shouldForceShowCaptcha,
				createCaptchaClass: $createCaptchaClass,
			) {
				public function __construct(
					public string|bool|null $expectedConfirmEditNeededForCaptchaValue,
					public ?string $expectedHCaptchaSiteKeyValue,
					public bool $expectedMobileHCaptchaAbuseFilterEnabled,
					public ?bool $isVisualEditorAvailable,
					public ?bool $isMobileFrontendAvailable,
					public bool $isAbuseFilterAvailable,
					public bool $isHCaptchaEnabledInMobileFrontend,
					public bool $shouldCheckResult,
					public bool $shouldForceShowCaptcha,
					public string $createCaptchaClass,
				) {
				}
			};

			yield sprintf(
				'VisualEditor is %s, ' .
					'MobileFrontend editor is %s, AbuseFilter is %s, hCaptcha in MF is %s, ' .
					'ConfirmEdit captcha is %s and %s with force captcha %s',
				match ( $testCase->isVisualEditorAvailable ) {
					null => 'not installed',
					true => 'available',
					false => 'not available',
				},
				match ( $testCase->isMobileFrontendAvailable ) {
					null => 'not installed',
					true => 'available',
					false => 'not available',
				},
				$testCase->isAbuseFilterAvailable ?
					'available' :
					'not available',
				$testCase->isHCaptchaEnabledInMobileFrontend ? 'enabled' : 'disabled',
				$testCase->createCaptchaClass,
				$testCase->shouldCheckResult ?
					'required for a generic edit' :
					'not needed for a generic edit',
				$testCase->shouldForceShowCaptcha ? 'set' : 'not set'
			) => [
				'testCase' => $testCase
			];
		}
	}
}
