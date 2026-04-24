<?php

namespace MediaWiki\Extension\ConfirmEdit\Tests\Unit\Hooks\Handlers;

use MediaWiki\Extension\ConfirmEdit\Hooks\Handlers\RLRegisterModulesHandler;
use MediaWiki\Extension\ConfirmEdit\Services\LoadedCaptchasProvider;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\ResourceLoader\ResourceLoader;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\Hooks\Handlers\RLRegisterModulesHandler
 */
class RLRegisterModulesHandlerTest extends MediaWikiUnitTestCase {
	/** @dataProvider provideCaptchaModuleRegistration */
	public function testCaptchaModuleRegistration( array $captchasEnabled, array $expectedModuleNames ) {
		$mockLoadedCaptchasProvider = $this->createMock( LoadedCaptchasProvider::class );
		$mockLoadedCaptchasProvider->method( 'getLoadedCaptchas' )
			->willReturn( $captchasEnabled );

		$handler = new RLRegisterModulesHandler(
			$mockLoadedCaptchasProvider,
			$this->createMock( ExtensionRegistry::class )
		);

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
				'expectedModuleNames' => [
					'ext.confirmEdit.CaptchaInputWidget',
					'ext.confirmEdit.CaptchaWidget',
				],
			],
			'hCaptcha is enabled' => [
				'captchasEnabled' => [ 'SimpleCaptcha', 'HCaptcha' ],
				'expectedModuleNames' => [
					'ext.confirmEdit.CaptchaInputWidget',
					'ext.confirmEdit.CaptchaWidget',
					'ext.confirmEdit.hCaptcha',
					'ext.confirmEdit.hCaptcha.styles',
				],
			],
		];
	}

	/** @dataProvider provideOptionalMessagesForCaptchaInputWidgetModule */
	public function testOptionalMessagesForCaptchaInputWidgetModule(
		array $captchasEnabled,
		array $expectedMessages
	): void {
		$mockExtensionRegistry = $this->createMock( ExtensionRegistry::class );
		$mockExtensionRegistry->method( 'isLoaded' )
			->willReturnCallback( static fn ( string $name ) => in_array( $name, $captchasEnabled, true ) );

		$mockLoadedCaptchasProvider = $this->createMock( LoadedCaptchasProvider::class );
		$mockLoadedCaptchasProvider->method( 'getLoadedCaptchas' )
			->willReturn( $captchasEnabled );

		$handler = new RLRegisterModulesHandler( $mockLoadedCaptchasProvider, $mockExtensionRegistry );

		$rl = $this->createMock( ResourceLoader::class );
		$rl->expects( $this->once() )
			->method( 'register' )
			->with( $this->callback( function ( array $modules ) use ( $expectedMessages ) {
				$this->assertArrayHasKey( 'ext.confirmEdit.CaptchaInputWidget', $modules );
				$this->assertArrayContains(
					$expectedMessages,
					$modules['ext.confirmEdit.CaptchaInputWidget']['messages']
				);
				return true;
			} ) );
		$handler->onResourceLoaderRegisterModules( $rl );
	}

	public static function provideOptionalMessagesForCaptchaInputWidgetModule(): array {
		return [
			'QuestyCaptcha is enabled' => [
				'captchasEnabled' => [ 'QuestyCaptcha' ],
				'expectedMessages' => [ 'questycaptcha-edit' ],
			],
			'FancyCaptcha is enabled' => [
				'captchasEnabled' => [ 'FancyCaptcha', 'HCaptcha' ],
				'expectedMessages' => [ 'fancycaptcha-edit' ],
			],
		];
	}
}
