<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration\Api\Rest\Handler;

use MediaWiki\Config\HashConfig;
use MediaWiki\Extension\ConfirmEdit\Api\Rest\Handler\PostHCaptchaTokenForBlockHandler;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\HCaptcha;
use MediaWiki\Request\WebRequest;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\RequestData;
use MediaWiki\Tests\Rest\Handler\HandlerTestTrait;
use MediaWiki\User\UserIdentity;
use MediaWikiIntegrationTestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\Api\Rest\Handler\PostHCaptchaTokenForBlockHandler
 * @group Database
 */
class PostHCaptchaTokenForBlockHandlerTest extends MediaWikiIntegrationTestCase {

	use HandlerTestTrait;

	private function newHandler(
		?string $siteKey = 'test-site-key'
	): PostHCaptchaTokenForBlockHandler {
		return new PostHCaptchaTokenForBlockHandler(
			new HashConfig( [
				'HCaptchaBlockedIpEditingScoreCollectionSiteKey' => $siteKey
			] ),
			$this->getServiceContainer()->getHookContainer(),
			$this->getServiceContainer()->getUserFactory()
		);
	}

	public function testRunReturns204OnSuccess(): void {
		$mockHCaptcha = $this->createMock( HCaptcha::class );
		$mockHCaptcha
			->method( 'retrieveRiskScore' )
			->willReturn( 0.5 );

		$handler = $this->newHandler();
		$handler->setHCaptcha( $mockHCaptcha );

		$response = $this->executeHandler(
			$handler,
			new RequestData( [ 'method' => 'POST' ] ),
			[],
			[],
			[],
			[
				'riskScoreToken' => 'test-token',
				'localBlockIds' => [ 1, 2 ],
				'globalBlockIds' => [],
			]
		);

		$this->assertSame( 204, $response->getStatusCode() );
	}

	public function testRunFiresHookWithCorrectParams(): void {
		$mockHCaptcha = $this->createMock( HCaptcha::class );
		$mockHCaptcha
			->method( 'retrieveRiskScore' )
			->willReturn( 0.5 );

		$hookFired = false;
		$this->setTemporaryHook(
			'ConfirmEditHCaptchaRiskScoreRetrievedForBlocks',
			function (
				float $riskScore,
				array $localBlockIds,
				array $globalBlockIds,
				UserIdentity $user,
				string $pageViewId,
				WebRequest $request
			) use ( &$hookFired ): void {
				$hookFired = true;
				$this->assertSame( 0.5, $riskScore );
				$this->assertSame( [ 1, 2 ], $localBlockIds );
				$this->assertSame( [ 3 ], $globalBlockIds );
				$this->assertSame( '', $pageViewId );
			}
		);

		$handler = $this->newHandler();
		$handler->setHCaptcha( $mockHCaptcha );
		$this->executeHandler(
			$handler,
			new RequestData( [ 'method' => 'POST' ] ),
			[],
			[],
			[],
			[
				'riskScoreToken' => 'test-token',
				'localBlockIds' => [ 1, 2 ],
				'globalBlockIds' => [ 3 ],
			]
		);

		$this->assertTrue( $hookFired, 'The hook should have been fired' );
	}

	public function testRunPassesPageViewIdToHook(): void {
		$mockHCaptcha = $this->createMock( HCaptcha::class );
		$mockHCaptcha
			->method( 'retrieveRiskScore' )
			->willReturn( 0.5 );

		$hookFired = false;
		$this->setTemporaryHook(
			'ConfirmEditHCaptchaRiskScoreRetrievedForBlocks',
			function (
				float $riskScore,
				array $localBlockIds,
				array $globalBlockIds,
				UserIdentity $user,
				string $pageViewId,
				WebRequest $request
			) use ( &$hookFired ): void {
				$hookFired = true;
				$this->assertSame( 'abc123', $pageViewId );
			}
		);

		$handler = $this->newHandler();
		$handler->setHCaptcha( $mockHCaptcha );
		$this->executeHandler(
			$handler,
			new RequestData( [ 'method' => 'POST' ] ),
			[],
			[],
			[],
			[
				'riskScoreToken' => 'test-token',
				'localBlockIds' => [ 1 ],
				'globalBlockIds' => [],
				'pageViewId' => '  abc123  ',
			]
		);

		$this->assertTrue( $hookFired, 'The hook should have been fired' );
	}

	public function testRunPassesSitekeyToRetrieveRiskScore(): void {
		$mockHCaptcha = $this->createMock( HCaptcha::class );
		$mockHCaptcha
			->expects( $this->once() )
			->method( 'retrieveRiskScore' )
			->with(
				$this->isInstanceOf( WebRequest::class ),
				'test-token',
				$this->anything(),
				[ 'test-site-key' ]
			)->willReturn( 0.3 );

		$handler = $this->newHandler( 'test-site-key' );
		$handler->setHCaptcha( $mockHCaptcha );
		$this->executeHandler(
			$handler,
			new RequestData( [ 'method' => 'POST' ] ),
			[],
			[],
			[],
			[
				'riskScoreToken' => 'test-token',
				'localBlockIds' => [ 1 ],
				'globalBlockIds' => [],
			]
		);
	}

	public function testRunReturns204AndLogsInfoWhenSitekeyNotConfigured(): void {
		$mockLogger = $this->createMock( LoggerInterface::class );
		$mockLogger
			->expects( $this->once() )
			->method( 'info' )
			->with(
				'hCaptcha block token request received but no sitekey is configured.',
				$this->anything()
			);
		$this->setLogger( 'captcha', $mockLogger );

		$response = $this->executeHandler(
			$this->newHandler( siteKey: null ),
			new RequestData( [ 'method' => 'POST' ] ),
			[],
			[],
			[],
			[
				'riskScoreToken' => 'test-token',
				'localBlockIds' => [ 1 ],
				'globalBlockIds' => [],
			]
		);

		$this->assertSame( 204, $response->getStatusCode() );
	}

	public function testRunReturns204AndLogsErrorWhenRiskScoreRetrievalFails(): void {
		$mockHCaptcha = $this->createMock( HCaptcha::class );
		$mockHCaptcha
			->method( 'retrieveRiskScore' )
			->willReturn( false );

		$mockLogger = $this->createMock( LoggerInterface::class );
		$mockLogger
			->expects( $this->once() )
			->method( 'error' )
			->with(
				'hCaptcha siteverify failed when collecting risk score for blocked user.',
				$this->anything()
			);
		$this->setLogger( 'captcha', $mockLogger );

		$handler = $this->newHandler();
		$handler->setHCaptcha( $mockHCaptcha );
		$response = $this->executeHandler(
			$handler,
			new RequestData( [ 'method' => 'POST' ] ),
			[],
			[],
			[],
			[
				'riskScoreToken' => 'test-token',
				'localBlockIds' => [ 1 ],
				'globalBlockIds' => [],
			]
		);

		$this->assertSame( 204, $response->getStatusCode() );
	}

	/** @dataProvider provideMissingParams */
	public function testRunReturns204AndLogsWarningWhenParamsMissing(
		array $body,
		bool $hasRiskScoreToken,
		bool $hasLocalBlockIds,
		bool $hasGlobalBlockIds
	): void {
		$mockLogger = $this->createMock( LoggerInterface::class );
		$mockLogger
			->expects( $this->once() )
			->method( 'warning' )
			->with(
				'hCaptcha block token request received with missing required params.',
				$this->callback(
					static fn ( array $context ) => $context['hasRiskScoreToken'] === $hasRiskScoreToken
						&& $context['hasLocalBlockIds'] === $hasLocalBlockIds
						&& $context['hasGlobalBlockIds'] === $hasGlobalBlockIds
				)
			);
		$this->setLogger( 'captcha', $mockLogger );

		$response = $this->executeHandler(
			$this->newHandler(),
			new RequestData( [ 'method' => 'POST' ] ),
			[],
			[],
			[],
			$body
		);

		$this->assertSame( 204, $response->getStatusCode() );
	}

	public static function provideMissingParams(): array {
		return [
			'empty riskScoreToken' => [
				'body' => [
					'riskScoreToken' => '',
					'localBlockIds' => [ 1 ],
					'globalBlockIds' => [],
				],
				'hasRiskScoreToken' => false,
				'hasLocalBlockIds' => true,
				'hasGlobalBlockIds' => false,
			],
			'empty localBlockIds and globalBlockIds' => [
				'body' => [
					'riskScoreToken' => 'test-token',
					'localBlockIds' => [],
					'globalBlockIds' => [],
				],
				'hasRiskScoreToken' => true,
				'hasLocalBlockIds' => false,
				'hasGlobalBlockIds' => false,
			],
		];
	}

	public function testRunReturns429WhenRateLimitExceeded(): void {
		$this->mergeMwGlobalArrayValue(
			'wgRateLimits',
			[ 'post-hcaptcha-token' => [ 'user' => [ 1, 86400 ] ] ]
		);

		$mockHCaptcha = $this->createMock( HCaptcha::class );
		$mockHCaptcha
			->method( 'retrieveRiskScore' )
			->willReturn( false );

		$user = $this->getTestUser()->getUser();
		$requestData = new RequestData( [ 'method' => 'POST' ] );
		$body = [
			'riskScoreToken' => 'test-token',
			'localBlockIds' => [ 1 ],
			'globalBlockIds' => [],
		];

		$firstHandler = $this->newHandler();
		$firstHandler->setHCaptcha( $mockHCaptcha );
		$this->executeHandler(
			$firstHandler,
			$requestData,
			[],
			[],
			[],
			$body,
			$user
		);

		$this->expectExceptionObject(
			new HttpException( 'Too many requests', 429 )
		);
		$this->executeHandler(
			$this->newHandler(),
			$requestData,
			[],
			[],
			[],
			$body,
			$user
		);
	}
}
