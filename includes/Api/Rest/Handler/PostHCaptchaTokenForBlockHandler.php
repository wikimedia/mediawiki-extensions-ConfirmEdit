<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Api\Rest\Handler;

use BadMethodCallException;
use MediaWiki\Block\Block;
use MediaWiki\Config\Config;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\HCaptcha;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\HCaptchaBlocksLookup;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\RiskScoreCrawlerFilter;
use MediaWiki\Extension\ConfirmEdit\Hooks\HookRunner;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Permissions\Authority;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserIdentity;
use Psr\Log\LoggerInterface;
use Wikimedia\ParamValidator\ParamValidator;

class PostHCaptchaTokenForBlockHandler extends SimpleHandler {

	private LoggerInterface $logger;
	private ?HCaptcha $hCaptcha = null;

	public function __construct(
		private readonly Config $config,
		private readonly HookContainer $hookContainer,
		private readonly UserFactory $userFactory,
		private readonly HCaptchaBlocksLookup $blocksLookup,
		private readonly TitleFactory $titleFactory,
		private readonly RiskScoreCrawlerFilter $crawlerFilter,
	) {
		$this->logger = LoggerFactory::getInstance( 'captcha' );
	}

	/**
	 * @throws HttpException
	 */
	public function run(): Response {
		// Backstop in case a crawler reaches the endpoint despite the page-display skip.
		if ( $this->crawlerFilter->isExcludedCrawler( RequestContext::getMain()->getRequest() ) ) {
			return $this->newNoContentResponse();
		}

		$authority = $this->getAuthority();
		$this->assertHasAccess( $authority );

		$body = $this->getValidatedBody() ?? [];
		$riskScoreToken = $body['riskScoreToken'] ?? '';
		if ( !$riskScoreToken ) {
			$this->logger->warning(
				'hCaptcha block token request received with missing required params.',
				[ 'hasRiskScoreToken' => false ]
			);

			return $this->newNoContentResponse();
		}

		$siteKey = $this->config->get( 'HCaptchaBlockedIpEditingScoreCollectionSiteKey' );
		if ( !$siteKey ) {
			$this->logger->info(
				'hCaptcha block token request received but no sitekey is configured.',
				[ 'hasRiskScoreToken' => true ]
			);

			return $this->newNoContentResponse();
		}

		$blocks = $this->resolveBlocks( $authority, (string)( $body['page'] ?? '' ) );
		if ( !count( $blocks ) ) {
			return $this->newNoContentResponse();
		}

		return $this->dispatchRiskScore(
			$riskScoreToken,
			$siteKey,
			$authority->getUser(),
			$blocks,
			trim( $body['pageViewId'] ?? '' )
		);
	}

	/**
	 * @param string $riskScoreToken
	 * @param string $siteKey
	 * @param UserIdentity $user
	 * @param Block[] $blocks
	 * @param string $pageViewId
	 */
	private function dispatchRiskScore(
		string $riskScoreToken,
		string $siteKey,
		UserIdentity $user,
		array $blocks,
		string $pageViewId
	): Response {
		$request = RequestContext::getMain()->getRequest();
		$riskScore = $this->getHCaptcha()->retrieveRiskScore(
			$request,
			$riskScoreToken,
			$user,
			[ $siteKey ]
		);

		if ( $riskScore === false ) {
			$this->logger->info(
				'hCaptcha siteverify failed when collecting risk score for blocked user.',
				[
					'user' => $user->getName(),
					'error' => $this->getHCaptcha()->getError(),
				]
			);

			return $this->newNoContentResponse();
		}

		$hookRunner = new HookRunner( $this->hookContainer );
		$hookRunner->onConfirmEditHCaptchaRiskScoreRetrievedForBlocks(
			$riskScore,
			$blocks,
			$user,
			$pageViewId,
			$request
		);

		return $this->newNoContentResponse();
	}

	private function newNoContentResponse(): Response {
		$response = $this->getResponseFactory()->create();
		$response->setStatus( 204 );

		return $response;
	}

	private function resolveBlocks( Authority $authority, string $page ): array {
		$title = $this->titleFactory->newFromText( $page );
		if ( !$title ) {
			return [];
		}

		return $this->blocksLookup->getBlocksRequiringHCaptcha( $title, $authority->getBlock() );
	}

	/** @inheritDoc */
	public function getBodyParamSettings(): array {
		return [
			'riskScoreToken' => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
			'pageViewId' => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
			],
			'page' => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
			],
		];
	}

	private function getHCaptcha(): HCaptcha {
		$this->hCaptcha ??= new HCaptcha();
		return $this->hCaptcha;
	}

	/**
	 * @internal For use in tests only.
	 */
	public function setHCaptcha( HCaptcha $hCaptcha ): void {
		if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
			throw new BadMethodCallException(
				'Cannot inject an HCaptcha instance in operation.'
			);
		}

		$this->hCaptcha = $hCaptcha;
	}

	/**
	 * @throws HttpException
	 */
	private function assertHasAccess( Authority $authority ): void {
		$performingUser = $this->userFactory->newFromUserIdentity(
			$authority->getUser()
		);

		if ( $performingUser->pingLimiter( 'post-hcaptcha-token' ) ) {
			throw new HttpException( 'Too many requests', 429 );
		}
	}
}
