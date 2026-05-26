<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration\Hooks\Handlers;

use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\Hooks\Handlers\UserGetRightsHookHandler
 * @group Database
 */
class UserGetRightsHookHandlerTest extends MediaWikiIntegrationTestCase {
	/** @dataProvider provideTestOnUserGetRightsMinEditCount */
	public function testOnUserGetRightsMinEditCount( $minEditCount, $expected ) {
		$this->overrideConfigValue( 'SkipCaptchaMinimumEditCount', $minEditCount );

		// skipcaptcha is granted to sysop by default
		$testUser = $this->getTestSysop()->getUser();

		// make an edit with the user to test min edit counts around it
		$this->editPage(
			'Test page',
			'Test Content',
			'test',
			NS_MAIN,
			$testUser
		);

		$userRights = [ 'read', 'skipcaptcha' ];
		$this->getServiceContainer()->getHookContainer()->run(
			'UserGetRights',
			[ $testUser, &$userRights ]
		);
		$this->assertSame( $expected, $userRights );
	}

	public static function provideTestOnUserGetRightsMinEditCount() {
		return [
			'meets min edit count' => [ 1, [ 'read', 'skipcaptcha' ] ],
			'does not meet min edit count' => [ 2, [ 'read' ] ]
		];
	}
}
