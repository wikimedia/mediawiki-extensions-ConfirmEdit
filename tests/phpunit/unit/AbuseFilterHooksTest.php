<?php

namespace MediaWiki\Extension\ConfirmEdit\Test\Unit\AbuseFilter;

use MediaWiki\Extension\ConfirmEdit\AbuseFilterHooks;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\AbuseFilterHooks
 */
class AbuseFilterHooksTest extends MediaWikiUnitTestCase {

	public function testOnAbuseFilterCustomActions() {
		$abuseFilterHooks = new AbuseFilterHooks();
		$actions = [];
		$abuseFilterHooks->onAbuseFilterCustomActions( $actions );
		$this->assertArrayHasKey( 'showcaptcha', $actions );
	}
}
