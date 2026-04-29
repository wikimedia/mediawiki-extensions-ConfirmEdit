<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Tests\Unit\ReCaptchaNoCaptcha;

use MediaWiki\Extension\ConfirmEdit\ReCaptchaNoCaptcha\ReCaptchaNoCaptcha;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\ReCaptchaNoCaptcha\ReCaptchaNoCaptcha
 */
class ReCaptchaNoCaptchaTest extends MediaWikiUnitTestCase {
	public function testGetApiParams(): void {
		$this->assertArrayEquals( [ 'g-recaptcha-response' ], ( new ReCaptchaNoCaptcha() )->getApiParams() );
	}
}
