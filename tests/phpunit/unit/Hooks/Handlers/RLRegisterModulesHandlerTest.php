<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Tests\Unit\Hooks\Handlers;

use MediaWiki\Extension\ConfirmEdit\Hooks\Handlers\RLRegisterModulesHandler;
use MediaWiki\Extension\ConfirmEdit\Services\LoadedCaptchasProvider;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\ResourceLoader\CodexModule;
use MediaWiki\ResourceLoader\ResourceLoader;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\Hooks\Handlers\RLRegisterModulesHandler
 */
class RLRegisterModulesHandlerTest extends MediaWikiUnitTestCase {

	private function getHookHandler( array $captchasEnabled ): RLRegisterModulesHandler {
		$mockExtensionRegistry = $this->createMock( ExtensionRegistry::class );
		$mockExtensionRegistry->method( 'isLoaded' )
			->willReturnCallback( static fn ( string $name ) => in_array( $name, $captchasEnabled, true ) );

		$mockLoadedCaptchasProvider = $this->createMock( LoadedCaptchasProvider::class );
		$mockLoadedCaptchasProvider->method( 'getLoadedCaptchas' )
			->willReturn( $captchasEnabled );

		return new RLRegisterModulesHandler( $mockLoadedCaptchasProvider, $mockExtensionRegistry );
	}

	/** @dataProvider provideCaptchaModuleRegistration */
	public function testCaptchaModuleRegistration( array $captchasEnabled, array $expectedModuleNames ): void {
		$handler = $this->getHookHandler( $captchasEnabled );

		// Run the hook with a mock ResourceLoader instance that stores the list of registered modules for
		// later assertion
		$rlModules = [];
		$rl = $this->createMock( ResourceLoader::class );
		$rl->method( 'register' )
			->willReturnCallback( static function ( array $modules ) use ( &$rlModules ) {
				$rlModules = array_merge( $rlModules, $modules );
			} );
		$handler->onResourceLoaderRegisterModules( $rl );

		$this->assertArrayEquals( $expectedModuleNames, array_keys( $rlModules ) );
	}

	public static function provideCaptchaModuleRegistration(): array {
		return [
			'hCaptcha is not enabled' => [
				'captchasEnabled' => [ 'SimpleCaptcha' ],
				'expectedModuleNames' => [ 'ext.confirmEdit.CaptchaWidget' ],
			],
			'hCaptcha is enabled' => [
				'captchasEnabled' => [ 'SimpleCaptcha', 'HCaptcha' ],
				'expectedModuleNames' => [
					'ext.confirmEdit.CaptchaWidget',
					'ext.confirmEdit.hCaptcha',
					'ext.confirmEdit.hCaptcha.gradeC',
					'ext.confirmEdit.hCaptcha.styles',
				],
			],
		];
	}

	/** @dataProvider provideOptionalMessagesForCaptchaWidgetModules */
	public function testOptionalMessagesForCaptchaWidgetModules(
		array $captchasEnabled,
		array $expectedMessages
	): void {
		$handler = $this->getHookHandler( $captchasEnabled );

		$rl = $this->createMock( ResourceLoader::class );
		$rl->expects( $this->once() )
			->method( 'register' )
			->with( $this->callback( function ( array $modules ) use ( $expectedMessages ) {
				$this->assertArrayHasKey( 'ext.confirmEdit.CaptchaWidget', $modules );
				$this->assertArrayContains(
					$expectedMessages,
					$modules['ext.confirmEdit.CaptchaWidget']['messages']
				);
				return true;
			} ) );
		$handler->onResourceLoaderRegisterModules( $rl );
	}

	public static function provideOptionalMessagesForCaptchaWidgetModules(): array {
		return [
			'QuestyCaptcha is enabled' => [
				'captchasEnabled' => [ 'QuestyCaptcha' ],
				'expectedMessages' => [ 'questycaptcha-edit' ],
			],
			'FancyCaptcha and HCaptcha is enabled' => [
				'captchasEnabled' => [ 'FancyCaptcha', 'HCaptcha' ],
				'expectedMessages' => [ 'fancycaptcha-edit', 'hcaptcha-force-show-captcha-edit' ],
			],
		];
	}

	/** @dataProvider provideCaptchaWidgetModuleUsesCodexComponentsWhenNeeded */
	public function testCaptchaWidgetModuleUsesCodexComponentsWhenNeeded(
		array $captchasEnabled,
		bool $shouldUseCodexModule
	): void {
		$handler = $this->getHookHandler( $captchasEnabled );

		$rl = $this->createMock( ResourceLoader::class );
		$rl->expects( $this->once() )
			->method( 'register' )
			->with( $this->callback( function ( array $modules ) use ( $shouldUseCodexModule ) {
				$this->assertArrayHasKey( 'ext.confirmEdit.CaptchaWidget', $modules );
				if ( $shouldUseCodexModule ) {
					$this->assertArrayContains(
						[
							'class' => CodexModule::class,
							'codexComponents' => [ 'CdxTextInput' ],
							'codexStyleOnly' => true,
						],
						$modules['ext.confirmEdit.CaptchaWidget']
					);
				} else {
					$this->assertArrayNotHasKey( 'codexComponents', $modules['ext.confirmEdit.CaptchaWidget'] );
				}
				return true;
			} ) );
		$handler->onResourceLoaderRegisterModules( $rl );
	}

	public static function provideCaptchaWidgetModuleUsesCodexComponentsWhenNeeded(): array {
		return [
			'QuestyCaptcha enabled' => [
				'captchasEnabled' => [ 'QuestyCaptcha' ],
				'shouldUseCodexModule' => true,
			],
			'SimpleCaptcha and HCaptcha enabled' => [
				'captchasEnabled' => [ 'SimpleCaptcha', 'HCaptcha' ],
				'shouldUseCodexModule' => true,
			],
			'HCaptcha only enabled' => [
				'captchasEnabled' => [ 'HCaptcha' ],
				'shouldUseCodexModule' => false,
			],
		];
	}
}
