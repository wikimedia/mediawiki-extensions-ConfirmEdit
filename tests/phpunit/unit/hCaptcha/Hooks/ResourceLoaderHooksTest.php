<?php

namespace MediaWiki\Extension\ConfirmEdit\Test\Unit\hCaptcha\Hooks;

use MediaWiki\Config\HashConfig;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\Hooks\ResourceLoaderHooks;
use MediaWiki\ResourceLoader\Context;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\hCaptcha\Hooks\ResourceLoaderHooks
 */
class ResourceLoaderHooksTest extends MediaWikiUnitTestCase {
	public function testGetHCaptchaResourceLoaderConfig() {
		$this->assertArrayEquals(
			[
				'hCaptchaSiteKey' => 'abc',
				'hCaptchaScriptURL' => 'https://test.com',
			],
			ResourceLoaderHooks::getHCaptchaResourceLoaderConfig(
				$this->createMock( Context::class ),
				new HashConfig( [
					'HCaptchaSiteKey' => 'abc',
					'HCaptchaApiUrl' => 'https://test.com'
				] )
			),
			false, true
		);
	}
}
