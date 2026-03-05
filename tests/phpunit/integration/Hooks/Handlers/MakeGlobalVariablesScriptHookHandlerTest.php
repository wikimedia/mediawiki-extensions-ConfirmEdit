<?php

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration\Hooks\Handlers;

use ExtensionRegistry;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\ConfirmEdit\Hooks;
use MediaWiki\Extension\ConfirmEdit\Hooks\Handlers\MakeGlobalVariablesScriptHookHandler;
use MediaWiki\Extension\VisualEditor\Services\VisualEditorAvailabilityLookup;
use MediaWiki\Title\Title;
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
		$this->overrideConfigValue(
			'HCaptchaVisualEditorOnLoadIntegrationEnabled',
			$testCase->hCaptchaVisualEditorOnLoadIntegrationEnabled
		);
		$this->clearHook( 'ConfirmEditCaptchaClass' );

		$out = RequestContext::getMain()->getOutput();
		$out->setTitle( Title::newFromText( __METHOD__ ) );

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
				return false;
			} );

		Hooks::getInstance( 'create' )
			->setForceShowCaptcha( $testCase->shouldForceShowCaptcha );

		// Call the hook and expect that variable is added if $isAvailable is true
		$vars = [];
		$objectUnderTest = new MakeGlobalVariablesScriptHookHandler(
			$mockExtensionRegistry,
			$this->getServiceContainer()->getMainConfig(),
			$mockVisualEditorAvailabilityLookup,
			$mockMobileContext
		);
		$objectUnderTest->onMakeGlobalVariablesScript( $vars, $out );

		if ( $testCase->expectedConfirmEditNeededForCaptchaValue === null &&
			$testCase->expectedHCaptchaSiteKeyValue === null ) {
			$this->assertCount( 0, $vars );
			$this->assertNotContains(
				'ext.confirmEdit.hCaptcha',
				$out->getModules()
			);
		} else {
			$expected = [];
			if ( $testCase->expectedConfirmEditNeededForCaptchaValue !== null ) {
				$expected['wgConfirmEditCaptchaNeededForGenericEdit'] =
					$testCase->expectedConfirmEditNeededForCaptchaValue;

				if ( $testCase->expectedConfirmEditNeededForCaptchaValue === 'hcaptcha' ) {
					$expected['wgConfirmEditHCaptchaSiteKey'] = 'foo';

					if ( $testCase->isMobileFrontendAvailable &&
						$testCase->isHCaptchaEnabledInMobileFrontend ) {
						$this->assertContains(
							'ext.confirmEdit.hCaptcha',
							$out->getModules()
						);
					}
				}
			}
			if ( $testCase->expectedHCaptchaSiteKeyValue !== null ) {
				$expected['wgConfirmEditHCaptchaSiteKey'] = $testCase->expectedHCaptchaSiteKeyValue;
			}
			if ( $testCase->expectedHCaptchaVisualEditorIntegrationEnabledValue !== null ) {
				$expected['wgConfirmEditHCaptchaVisualEditorOnLoadIntegrationEnabled'] =
					$testCase->expectedHCaptchaVisualEditorIntegrationEnabledValue;
			}
			if ( $testCase->isMobileFrontendAvailable &&
				$testCase->isHCaptchaEnabledInMobileFrontend &&
				$testCase->expectedConfirmEditNeededForCaptchaValue === 'hcaptcha' ) {
				$expected['wgMobileFrontendSourceEditorInitializeModules'] = [
					'ext.confirmEdit.hCaptcha'
				];
			}

			$this->assertArrayEquals( $expected, $vars, false, true );
		}
	}

	public static function provideMakeGlobalVariablesScript(): iterable {
		$testCases = ArrayUtils::cartesianProduct(
			// VisualEditor availability (null for not installed)
			[ true, false, null ],
			// $wgHCaptchaVisualEditorOnLoadIntegrationEnabled value
			[ true, false ],
			// MobileFrontend editor availability (null for not installed)
			[ true, false, null ],
			// Does the user need to complete a captcha for any edit (a "generic" edit)
			[ true, false ],
			// The value returned by SimpleCaptcha::shouldForceShowCaptcha
			[ true, false ],
			// The captcha class used for create actions
			[ 'HCaptcha', 'SimpleCaptcha' ],
			// Whether hCaptcha support is enabled for the MobileFrontend
			[ true, false ]
		);

		foreach ( $testCases as $params ) {
			$expectedConfirmEditNeededForCaptchaValue = null;
			$expectedHCaptchaSiteKeyValue = null;
			$expectedHCaptchaVisualEditorOnLoadIntegrationEnabledValue = null;

			// The behaviour when VisualEditor and MobileFrontend are both not installed is tested by other unit
			// tests, so we don't need to repeat that here
			if ( $params[0] === null && $params[2] === null ) {
				continue;
			}

			// If VisualEditor is not installed or not available, there is no need to test when
			// $wgHCaptchaVisualEditorOnLoadIntegrationEnabled is true (because it will never be enabled)
			if ( $params[0] !== true && $params[1] === true ) {
				continue;
			}

			// The JavaScript config variables will be set if either VisualEditor or
			// MobileFrontend editor is available.
			if ( $params[0] === true || $params[2] === true ) {
				$expectedConfirmEditNeededForCaptchaValue = false;
			}

			// The JavaScript config variable will have a value of the captcha class in use if:
			// * A captcha is needed for a generic edit or SimpleCaptcha::shouldForceShowCaptcha returns true
			// * Either the VisualEditor or MobileFrontend editor is available
			if ( ( $params[0] === true || $params[2] === true ) && ( $params[3] === true || $params[4] === true ) ) {
				$expectedConfirmEditNeededForCaptchaValue = strtolower( $params[5] );

				if ( $expectedConfirmEditNeededForCaptchaValue === 'hcaptcha' ) {
					$expectedHCaptchaSiteKeyValue = 'foo';
					if ( $params[4] === true ) {
						$expectedHCaptchaSiteKeyValue = 'foo-always';
					}

					// wgConfirmEditHCaptchaVisualEditorOnLoadIntegrationEnabled is true if both:
					// * VisualEditor is available
					// * $wgHCaptchaVisualEditorOnLoadIntegrationEnabled is true
					$expectedHCaptchaVisualEditorOnLoadIntegrationEnabledValue = $params[0] === true &&
						$params[1] === true;
				}
			}

			// ::shouldForceShowCaptcha values only affect the test output if hCaptcha is needed for an edit,
			// so only test with it as false if not hCaptcha
			if ( $expectedConfirmEditNeededForCaptchaValue !== 'hcaptcha' && $params[4] === true ) {
				continue;
			}

			// The $wgHCaptchaVisualEditorOnLoadIntegrationEnabled values only affect the test if hCaptcha
			// is the captcha required for a generic edit, so only test with it as false if not hCaptcha
			if ( $expectedConfirmEditNeededForCaptchaValue !== 'hcaptcha' && $params[1] === true ) {
				continue;
			}

			$testCase = new class(
				expectedConfirmEditNeededForCaptchaValue:
					$expectedConfirmEditNeededForCaptchaValue,
				expectedHCaptchaSiteKeyValue: $expectedHCaptchaSiteKeyValue,
				expectedHCaptchaVisualEditorIntegrationEnabledValue:
					$expectedHCaptchaVisualEditorOnLoadIntegrationEnabledValue,
				isVisualEditorAvailable: $params[0],
				hCaptchaVisualEditorOnLoadIntegrationEnabled: $params[1],
				isMobileFrontendAvailable: $params[2],
				shouldCheckResult: $params[3],
				shouldForceShowCaptcha: $params[4],
				createCaptchaClass: $params[5],
				isHCaptchaEnabledInMobileFrontend: $params[6]
			) {
				public function __construct(
					public string|bool|null $expectedConfirmEditNeededForCaptchaValue,
					public ?string $expectedHCaptchaSiteKeyValue,
					public ?bool $expectedHCaptchaVisualEditorIntegrationEnabledValue,
					public ?bool $isVisualEditorAvailable,
					public bool $hCaptchaVisualEditorOnLoadIntegrationEnabled,
					public ?bool $isMobileFrontendAvailable,
					public bool $shouldCheckResult,
					public bool $shouldForceShowCaptcha,
					public string $createCaptchaClass,
					public bool $isHCaptchaEnabledInMobileFrontend
				) {
				}
			};

			yield sprintf(
				'VisualEditor is %s with integration %s, ' .
					'MobileFrontend editor is %s with integration %s, ' .
					'ConfirmEdit captcha is %s and %s with force captcha %s',
				match ( $testCase->isVisualEditorAvailable ) {
					null => 'not installed',
					true => 'available',
					false => 'not available',
				},
				$testCase->hCaptchaVisualEditorOnLoadIntegrationEnabled ?
					'enabled' : 'disabled',
				match ( $testCase->isMobileFrontendAvailable ) {
					null => 'not installed',
					true => 'available',
					false => 'not available',
				},
				match ( $testCase->isHCaptchaEnabledInMobileFrontend ) {
					true => 'enabled',
					false => 'disabled',
				},
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
