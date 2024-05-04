<?php

namespace MediaWiki\Extension\ConfirmEdit\Test\Integration\AbuseFilter;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\AbuseFilter\Consequences\Parameters;
use MediaWiki\Extension\ConfirmEdit\AbuseFilter\CaptchaConsequence;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\AbuseFilter\CaptchaConsequence
 */
class CaptchaConsequenceTest extends MediaWikiIntegrationTestCase {

	public function testExecute() {
		$parameters = $this->createMock( Parameters::class );
		$parameters->method( 'getAction' )->willReturn( 'edit' );
		$captchaConsequence = new CaptchaConsequence( $parameters );
		$request = RequestContext::getMain();
		$this->assertNull( $request->getRequest()->getVal(
			CaptchaConsequence::FLAG
		) );
		$captchaConsequence->execute();
		$this->assertTrue(
			$request->getRequest()->getBool(
				CaptchaConsequence::FLAG
			)
		);
	}

}
