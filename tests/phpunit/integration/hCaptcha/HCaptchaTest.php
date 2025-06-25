<?php

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration\hCaptcha;

use MediaWiki\Extension\ConfirmEdit\hCaptcha\HCaptcha;
use MediaWiki\Extension\ConfirmEdit\Tests\Integration\MockHCaptchaConfigTrait;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Json\FormatJson;
use MediaWiki\MainConfigNames;
use MediaWiki\Request\ContentSecurityPolicy;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Status\Status;
use MediaWikiIntegrationTestCase;
use MockHttpTrait;
use MWHttpRequest;
use Psr\Log\LoggerInterface;
use StatusValue;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\hCaptcha\HCaptcha
 */
class HCaptchaTest extends MediaWikiIntegrationTestCase {
	use MockHCaptchaConfigTrait;
	use MockHttpTrait;

	public function testPassCaptchaForHttpError() {
		$this->overrideConfigValue( 'HCaptchaSecretKey', 'secretkey' );
		$this->overrideConfigValue( 'HCaptchaProxy', 'proxy.test.com' );

		// Mock that the site-verify URL call will fail with a HTTP 500 error
		$mwHttpRequest = $this->createMock( MWHttpRequest::class );
		$mwHttpRequest->method( 'execute' )
			->willReturn( Status::wrap( StatusValue::newFatal( 'http-error-500' ) ) );
		$mwHttpRequest->method( 'getStatus' )
			->willReturn( 500 );
		$this->installMockHttp( $mwHttpRequest );

		// Mock HttpRequestFactory directly so that we can check the URL and options are as expected.
		// Other tests do not check this as it should be fine to check this once.
		$mockHttpRequestFactory = $this->createMock( HttpRequestFactory::class );
		$mockHttpRequestFactory->method( 'create' )
			->willReturnCallback( function ( $url, $options ) use ( $mwHttpRequest ) {
				$this->assertSame( 'https://api.hcaptcha.com/siteverify', $url );
				$this->assertArrayEquals(
					[
						'method' => 'POST',
						'postData' => [ 'response' => 'abcdef', 'secret' => 'secretkey' ],
						'proxy' => 'proxy.test.com',
					],
					$options,
					false,
					true
				);
				return $mwHttpRequest;
			} );
		$this->setService( 'HttpRequestFactory', $mockHttpRequestFactory );

		// Verify that a log is created to indicate the error
		$mockLogger = $this->createMock( LoggerInterface::class );
		$mockLogger->expects( $this->once() )
			->method( 'info' )
			->with( 'Unable to validate response: http-error-500' );
		$this->setLogger( 'captcha', $mockLogger );

		// Attempt to pass the captcha using a fake response that we expect to pass to the API.
		$hCaptcha = new HCaptcha();
		$this->assertFalse( $hCaptcha->passCaptchaFromRequest(
			new FauxRequest( [ 'h-captcha-response' => 'abcdef' ] ),
			$this->getServiceContainer()->getUserFactory()->newAnonymous( '1.2.3.4' )
		) );

		// Verify that the captcha verification failed with an error of 'http'
		$this->assertSame( 'http', $hCaptcha->getError() );
	}

	public function testPassCaptchaForInvalidJsonResponse() {
		$this->overrideConfigValue( 'HCaptchaSecretKey', 'secretkey' );

		// Mock that the site-verify URL call will cause invalid JSON to be returned.
		$mwHttpRequest = $this->createMock( MWHttpRequest::class );
		$mwHttpRequest->method( 'execute' )
			->willReturn( Status::newGood() );
		$mwHttpRequest->method( 'getStatus' )
			->willReturn( 200 );
		$mwHttpRequest->method( 'getContent' )
			->willReturn( 'invalidjson:{' );
		$this->installMockHttp( $mwHttpRequest );

		// Verify that a log is created to indicate the error
		$mockLogger = $this->createMock( LoggerInterface::class );
		$mockLogger->expects( $this->once() )
			->method( 'info' )
			->with( 'Unable to validate response: json' );
		$this->setLogger( 'captcha', $mockLogger );

		// Attempt to pass the captcha, but expect that this fails
		$hCaptcha = new HCaptcha();
		$this->assertFalse( $hCaptcha->passCaptchaFromRequest(
			new FauxRequest( [ 'h-captcha-response' => 'abcdef' ] ),
			$this->getServiceContainer()->getUserFactory()->newAnonymous( '1.2.3.4' )
		) );
		$this->assertSame( 'json', $hCaptcha->getError() );
	}

	public function testPassCaptchaForErrorsInJsonResponse() {
		$this->overrideConfigValue( 'HCaptchaSecretKey', 'secretkey' );

		// Mock that the site-verify URL call will cause JSON with error codes from the hCaptcha API
		$mwHttpRequest = $this->createMock( MWHttpRequest::class );
		$mwHttpRequest->method( 'execute' )
			->willReturn( Status::newGood() );
		$mwHttpRequest->method( 'getStatus' )
			->willReturn( 200 );
		$mwHttpRequest->method( 'getContent' )
			->willReturn( FormatJson::encode( [ 'error-codes' => [ 'testingabc', 'test' ] ] ) );
		$this->installMockHttp( $mwHttpRequest );

		// Verify that a log is created to indicate the error
		$mockLogger = $this->createMock( LoggerInterface::class );
		$mockLogger->expects( $this->once() )
			->method( 'info' )
			->with( 'Unable to validate response: testingabc,test' );
		$this->setLogger( 'captcha', $mockLogger );

		// Attempt to pass the captcha, but expect that this fails
		$hCaptcha = new HCaptcha();
		$this->assertFalse( $hCaptcha->passCaptchaFromRequest(
			new FauxRequest( [ 'h-captcha-response' => 'abcdef' ] ),
			$this->getServiceContainer()->getUserFactory()->newAnonymous( '1.2.3.4' )
		) );
		$this->assertSame( 'hcaptcha-api', $hCaptcha->getError() );
	}

	/** @dataProvider providePassCaptcha */
	public function testPassCaptcha( bool $captchaPassedSuccessfully, array $mockApiResponse ) {
		$this->overrideConfigValue( 'HCaptchaSecretKey', 'secretkey' );

		// Mock that the site-verify URL call to respond with a successful response
		$mwHttpRequest = $this->createMock( MWHttpRequest::class );
		$mwHttpRequest->method( 'execute' )
			->willReturn( Status::newGood() );
		$mwHttpRequest->method( 'getStatus' )
			->willReturn( 200 );
		$mwHttpRequest->method( 'getContent' )
			->willReturn( FormatJson::encode( $mockApiResponse ) );
		$this->installMockHttp( $mwHttpRequest );

		// Expect that a debug log is created to indicate that the captcha either was solved or was not solved.
		$mockLogger = $this->createMock( LoggerInterface::class );
		$mockLogger->expects( $this->once() )
			->method( 'debug' )
			->with(
				'Captcha solution attempt for {user}',
				[
					'event' => 'captcha.solve',
					'user' => '1.2.3.4',
					'hcaptcha_success' => $mockApiResponse['success'],
					'hcaptcha_score' => $mockApiResponse['score'] ?? null,
					'hcaptcha_score_reason' => $mockApiResponse['score_reason'] ?? null,
					'hcaptcha_blob' => $mockApiResponse,
				]
			);
		$this->setLogger( 'captcha', $mockLogger );

		// Attempt to pass the captcha and expect that it passes
		$hCaptcha = new HCaptcha();
		$this->assertSame(
			$captchaPassedSuccessfully,
			$hCaptcha->passCaptchaFromRequest(
				new FauxRequest( [ 'h-captcha-response' => 'abcdef' ] ),
				$this->getServiceContainer()->getUserFactory()->newAnonymous( '1.2.3.4' )
			)
		);
		$this->assertNull( $hCaptcha->getError() );
	}

	public static function providePassCaptcha(): array {
		return [
			'Passes hCaptcha check' => [ true, [ 'success' => true, 'score' => 123, 'score_reason' => 'test' ] ],
			'Fails hCaptcha check, no score included in response' => [ false, [ 'success' => false ] ],
		];
	}

	public function testAddCSPSources() {
		$this->overrideConfigValues( [
			'HCaptchaCSPRules' => [ '*.abc.com' ],
			MainConfigNames::CSPHeader => true,
		] );

		$mockContentSecurityPolicy = $this->createMock( ContentSecurityPolicy::class );
		$expectedMethodsToCallForEachUrl = [ 'addDefaultSrc', 'addScriptSrc', 'addStyleSrc' ];
		foreach ( $expectedMethodsToCallForEachUrl as $method ) {
			$mockContentSecurityPolicy->expects( $this->once() )
				->method( $method )
				->with( '*.abc.com' );
		}

		HCaptcha::addCSPSources( $mockContentSecurityPolicy );
	}
}
