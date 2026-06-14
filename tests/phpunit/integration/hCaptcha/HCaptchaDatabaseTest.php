<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration\hCaptcha;

use MediaWiki\Content\ContentHandler;
use MediaWiki\Context\RequestContext;
use MediaWiki\EditPage\EditPage;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\HCaptcha;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Http\MWHttpRequest;
use MediaWiki\Json\FormatJson;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Status\Status;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use MockHttpTrait;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\hCaptcha\HCaptcha
 * @group Database
 */
class HCaptchaDatabaseTest extends MediaWikiIntegrationTestCase {
	use MockHttpTrait;

	public function testConfirmEditMergedWhenForceCaptchaSet() {
		$this->clearHook( 'ConfirmEditCanUserSkipCaptcha' );
		$this->setTemporaryHook( 'ConfirmEditTriggersCaptcha', static function ( $action, $title, &$result ) {
			$result = true;
		} );

		// Mock the site-verify URL call to respond with a successful response
		// to indicate the captcha did not pass
		$mwHttpRequest = $this->createMock( MWHttpRequest::class );
		$mwHttpRequest->method( 'execute' )
			->willReturn( Status::newGood() );
		$mwHttpRequest->method( 'getStatus' )
			->willReturn( 200 );
		$mwHttpRequest->method( 'getContent' )
			->willReturn( FormatJson::encode( [ 'success' => false ] ) );
		$this->installMockHttp( $mwHttpRequest );

		// Set up the request and context such that no captcha word is provided,
		// so that the captcha check will fail
		$user = $this->getTestUser()->getUser();
		$title = $this->getNonexistingTestPage()->getTitle();
		$status = Status::newGood();
		$context = RequestContext::getMain();
		$context->setUser( $user );
		$context->setRequest( new FauxRequest( [ 'captchaword' => 'abcdef' ] ) );
		$context->setTitle( $title );

		$simpleCaptcha = new HCaptcha();
		$simpleCaptcha->setForceShowCaptcha( true );

		$this->assertFalse( $simpleCaptcha->confirmEditMerged(
			$context, ContentHandler::makeContent( '', $title ), $status, '', $user, false
		) );
		$this->assertStatusError( 'hcaptcha-force-show-captcha-edit', $status );
	}

	public function testConfirmEditMergedWhenForceCaptchaSetAfterFirstCheck(): void {
		// Mock that the first CAPTCHA check has a successful siteverify API call (the second
		// should not reach the siteverify API call)
		$siteVerifyRequest = $this->createMock( MWHttpRequest::class );
		$siteVerifyRequest->method( 'execute' )
			->willReturn( Status::newGood() );
		$siteVerifyRequest->method( 'getContent' )
			->willReturn( FormatJson::encode( [
				'success' => true,
				'sitekey' => 'normal-key',
			] ) );

		// The first solve performs siteverify, but the force-show failure path should not.
		$mockHttpRequestFactory = $this->createMock( HttpRequestFactory::class );
		$mockHttpRequestFactory->expects( $this->once() )
			->method( 'create' )
			->willReturn( $siteVerifyRequest );
		$this->setService( 'HttpRequestFactory', $mockHttpRequestFactory );

		$hCaptcha = new HCaptcha();
		$hCaptcha->setConfig( [
			'HCaptchaAlwaysChallengeSiteKey' => 'challenge-key',
			'HCaptchaSiteKey' => 'normal-key',
		] );
		$hCaptcha->setAction( 'edit' );

		$request = new FauxRequest( [ 'h-captcha-response' => 'abcdef' ], true );

		// Expect that the first "force show captcha" check passes, so that we can see a difference further below
		$user = $this->getTestUser()->getUser();
		$this->assertTrue( $hCaptcha->passCaptchaFromRequest( $request, $user ) );
		$this->assertNull( $hCaptcha->getError() );
		$this->assertTrue( $hCaptcha->isCaptchaSolved() );

		$hCaptcha->setForceShowCaptcha( true );

		// Using ::confirmEditMerged to check, make sure that the force show captcha flag now being set
		// causes hCaptcha to be considered not passed and so causes a CAPTCHA edit failure
		$context = new RequestContext();
		$context->setUser( $user );
		$title = Title::makeTitle( NS_MAIN, 'ForceShowCaptchaTestPage' );
		$context->setTitle( $title );
		$context->setRequest( new FauxRequest( [ 'captchaword' => 'abcdef' ], true ) );

		$status = Status::newGood();
		$this->assertFalse(
			$hCaptcha->confirmEditMerged(
				$context,
				ContentHandler::makeContent( '', $title ),
				$status,
				'',
				$user,
				false
			),
			'Edit should fail when force-show mode is enabled after a normal captcha solve'
		);
		$this->assertSame( EditPage::AS_HOOK_ERROR_EXPECTED, $status->value );
		$this->assertArrayHasKey( 'captcha', $status->statusData );
		$this->assertSame( 'hcaptcha', $status->statusData['captcha']['type'] );
		$this->assertSame( 'challenge-key', $status->statusData['captcha']['key'] );

		$this->assertFalse( $hCaptcha->isCaptchaSolved() );
	}
}
