<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration\Api\Rest\Handler;

use MediaWiki\Block\Block;
use MediaWiki\Config\HashConfig;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\ConfirmEdit\Api\Rest\Handler\PostHCaptchaTokenForBlockHandler;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\HCaptcha;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\HCaptchaBlocksLookup;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\RiskScoreCrawlerFilter;
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
		?string $siteKey = 'test-site-key',
		?HCaptchaBlocksLookup $blocksLookup = null,
		array $skipUserAgents = []
	): PostHCaptchaTokenForBlockHandler {
		return new PostHCaptchaTokenForBlockHandler(
			new HashConfig( [
				'HCaptchaBlockedIpEditingScoreCollectionSiteKey' => $siteKey
			] ),
			$this->getServiceContainer()->getHookContainer(),
			$this->getServiceContainer()->getUserFactory(),
			$blocksLookup ?? $this->newBlocksLookupMock( [ 1, 2, 3 ] ),
			$this->getServiceContainer()->getTitleFactory(),
			new RiskScoreCrawlerFilter(
				new ServiceOptions(
					RiskScoreCrawlerFilter::CONSTRUCTOR_OPTIONS,
					[ 'HCaptchaBlockedIpEditingScoreSkipUserAgents' => $skipUserAgents ]
				)
			)
		);
	}

	private function newBlocksLookupMock( array $blockIds ): HCaptchaBlocksLookup {
		$mock = $this->createMock( HCaptchaBlocksLookup::class );
		$mock
			->method( 'getBlocksRequiringHCaptcha' )
			->willReturn( array_map( function ( $blockId ) {
				$block = $this->createMock( Block::class );
				$block->method( 'getId' )
					->willReturn( $blockId );
				return $block;
			}, $blockIds ) );

		return $mock;
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
				'page' => 'Test',
			]
		);

		$this->assertSame( 204, $response->getStatusCode() );
	}

	public function testRunFiresHookWithServerDerivedBlockIds(): void {
		$mockHCaptcha = $this->createMock( HCaptcha::class );
		$mockHCaptcha
			->method( 'retrieveRiskScore' )
			->willReturn( 0.5 );

		$hookFired = false;
		$this->setTemporaryHook(
			'ConfirmEditHCaptchaRiskScoreRetrievedForBlocks',
			function (
				float $riskScore,
				array $blocks,
				UserIdentity $user,
				string $pageViewId,
				WebRequest $request
			) use ( &$hookFired ): void {
				$hookFired = true;
				$this->assertSame( 0.5, $riskScore );
				$this->assertSame( [ 1, 2, 3 ], array_map( static fn ( $block ) => $block->getId(), $blocks ) );
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
				'page' => 'Test',
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
				array $blocks,
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
				'page' => 'Test',
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
				'page' => 'Test',
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
				'page' => 'Test',
			]
		);

		$this->assertSame( 204, $response->getStatusCode() );
	}

	public function testRunReturns204AndLogsInfoWhenRiskScoreRetrievalFails(): void {
		$mockHCaptcha = $this->createMock( HCaptcha::class );
		$mockHCaptcha
			->method( 'retrieveRiskScore' )
			->willReturn( false );
		$mockHCaptcha
			->method( 'getError' )
			->willReturn( 'http' );

		$mockLogger = $this->createMock( LoggerInterface::class );
		$mockLogger
			->expects( $this->once() )
			->method( 'info' )
			->with(
				'hCaptcha siteverify failed when collecting risk score for blocked user.',
				$this->callback( static fn ( array $context ): bool => $context['error'] === 'http' )
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
				'page' => 'Test',
			]
		);

		$this->assertSame( 204, $response->getStatusCode() );
	}

	public function testRunReturns204AndLogsWarningWhenRiskScoreTokenMissing(): void {
		$mockLogger = $this->createMock( LoggerInterface::class );
		$mockLogger
			->expects( $this->once() )
			->method( 'warning' )
			->with(
				'hCaptcha block token request received with missing required params.',
				[ 'hasRiskScoreToken' => false ]
			);
		$this->setLogger( 'captcha', $mockLogger );

		$response = $this->executeHandler(
			$this->newHandler(),
			new RequestData( [ 'method' => 'POST' ] ),
			[],
			[],
			[],
			[
				'riskScoreToken' => '',
				'page' => 'Test',
			]
		);

		$this->assertSame( 204, $response->getStatusCode() );
	}

	public function testRunReturns204WhenPageMissing(): void {
		$mockHCaptcha = $this->createMock( HCaptcha::class );
		$mockHCaptcha
			->expects( $this->never() )
			->method( 'retrieveRiskScore' );

		$blocksLookup = $this->createMock( HCaptchaBlocksLookup::class );
		$blocksLookup
			->expects( $this->never() )
			->method( 'getBlocksRequiringHCaptcha' );

		$handler = $this->newHandler( blocksLookup: $blocksLookup );
		$handler->setHCaptcha( $mockHCaptcha );
		$response = $this->executeHandler(
			$handler,
			new RequestData( [ 'method' => 'POST' ] ),
			[],
			[],
			[],
			[
				'riskScoreToken' => 'test-token',
			]
		);

		$this->assertSame( 204, $response->getStatusCode() );
	}

	public function testRunReturns204WhenNoQualifyingBlocks(): void {
		$mockHCaptcha = $this->createMock( HCaptcha::class );
		$mockHCaptcha
			->expects( $this->never() )
			->method( 'retrieveRiskScore' );

		$handler = $this->newHandler(
			blocksLookup: $this->newBlocksLookupMock( [] )
		);
		$handler->setHCaptcha( $mockHCaptcha );
		$response = $this->executeHandler(
			$handler,
			new RequestData( [ 'method' => 'POST' ] ),
			[],
			[],
			[],
			[
				'riskScoreToken' => 'test-token',
				'page' => 'Test',
			]
		);

		$this->assertSame( 204, $response->getStatusCode() );
	}

	public function testRunReturns204AndSkipsScoringForCrawlerUserAgent(): void {
		$mockHCaptcha = $this->createMock( HCaptcha::class );
		$mockHCaptcha
			->expects( $this->never() )
			->method( 'retrieveRiskScore' );

		RequestContext::getMain()->getRequest()->setHeader( 'User-Agent', 'ExampleBot/1.0' );

		$handler = $this->newHandler( skipUserAgents: [ '#ExampleBot#i' ] );
		$handler->setHCaptcha( $mockHCaptcha );

		$response = $this->executeHandler(
			$handler,
			new RequestData( [ 'method' => 'POST' ] ),
			[],
			[],
			[],
			[
				'riskScoreToken' => 'test-token',
				'page' => 'Test',
			]
		);

		$this->assertSame( 204, $response->getStatusCode() );
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
			'page' => 'Test',
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
