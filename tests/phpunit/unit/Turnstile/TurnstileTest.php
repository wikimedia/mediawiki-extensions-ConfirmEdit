<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Tests\Unit\Turnstile;

use MediaWiki\Extension\ConfirmEdit\Turnstile\Turnstile;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\Turnstile\Turnstile
 */
class TurnstileTest extends MediaWikiUnitTestCase {
	public function testGetApiParams(): void {
		$this->assertArrayEquals( [ 'cf-turnstile-response' ], ( new Turnstile() )->getApiParams() );
	}
}
