<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Tests\Unit\SimpleCaptcha;

use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
use MediaWikiUnitTestCase;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha
 */
class SimpleCaptchaTest extends MediaWikiUnitTestCase {
	public function testGetApiParams(): void {
		$this->assertArrayEquals( [ 'captchaid', 'captchaword' ], ( new SimpleCaptcha() )->getApiParams() );
	}

	public function testClearCaptchaSolved(): void {
		$captcha = new SimpleCaptcha();
		$this->assertNull( $captcha->isCaptchaSolved() );

		TestingAccessWrapper::newFromObject( $captcha )->setCaptchaSolved( true );
		$this->assertTrue( $captcha->isCaptchaSolved() );

		$captcha->clearCaptchaSolved();
		$this->assertNull( $captcha->isCaptchaSolved() );
	}
}
