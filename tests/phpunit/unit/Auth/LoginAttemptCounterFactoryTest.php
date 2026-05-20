<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Tests\Unit\Auth;

use MediaWiki\Extension\ConfirmEdit\Auth\LoginAttemptCounter;
use MediaWiki\Extension\ConfirmEdit\Auth\LoginAttemptCounterFactory;
use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\Auth\LoginAttemptCounterFactory
 */
class LoginAttemptCounterFactoryTest extends MediaWikiUnitTestCase {
	public function testConstructsLoginAttemptCounter(): void {
		$factory = new LoginAttemptCounterFactory();
		$this->assertInstanceOf(
			LoginAttemptCounter::class,
			$factory->newLoginAttemptCounter( new SimpleCaptcha() )
		);
	}
}
