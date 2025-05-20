<?php

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration;

use MediaWiki\Extension\ConfirmEdit\FancyCaptcha\FancyCaptcha;
use MediaWiki\Extension\ConfirmEdit\Hooks;
use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\Hooks
 */
class HooksTest extends MediaWikiIntegrationTestCase {

	public function testGetInstanceNewStyleTriggers() {
		$this->overrideConfigValues(
			[
				'CaptchaClass' => 'SimpleCaptcha',
				'CaptchaTriggers' => [
					// New style trigger
					'edit' => [
						'trigger' => true,
						'class' => 'FancyCaptcha',
					],
					// Old style trigger
					'move' => false,
				]
			]
		);

		// Returns the default for $wgCaptchaClass
		$this->assertInstanceOf( SimpleCaptcha::class, Hooks::getInstance() );

		// Returns the default for $wgCaptchaClass, because it uses the old style trigger (boolean)
		$this->assertInstanceOf( SimpleCaptcha::class, Hooks::getInstance( 'move' ) );

		// Returns the default for $wgCaptchaClass, because the trigger isn't defined
		$this->assertInstanceOf( SimpleCaptcha::class, Hooks::getInstance( 'foo' ) );

		// Returns the FancyCaptcha instance for the edit trigger
		$this->assertInstanceOf( FancyCaptcha::class, Hooks::getInstance( 'edit' ) );
	}
}
