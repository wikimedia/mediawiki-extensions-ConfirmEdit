<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Tests\Unit\SimpleCaptcha;

use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha
 */
class SimpleCaptchaTest extends MediaWikiUnitTestCase {
	public function testGetApiParams(): void {
		$this->assertArrayEquals( [ 'captchaid', 'captchaword' ], ( new SimpleCaptcha() )->getApiParams() );
	}
}
