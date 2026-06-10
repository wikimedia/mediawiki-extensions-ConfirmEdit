<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Api\Rest\Handler;

use BadMethodCallException;
use MediaWiki\Config\Config;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\HCaptcha;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\HCaptchaBlocksLookup;
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
	) {
		$this->logger = LoggerFactory::getInstance( 'captcha' );
	}

	/**
	 * @throws HttpException
	 */
	public function run(): Response {
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

		$blockIds = $this->resolveBlockIds( $authority, (string)( $body['page'] ?? '' ) );
		if ( $blockIds === null ) {
			return $this->newNoContentResponse();
		}

		return $this->dispatchRiskScore(
			$riskScoreToken,
			$siteKey,
			$authority->getUser(),
			$blockIds['local'],
			$blockIds['global'],
			trim( $body['pageViewId'] ?? '' )
		);
	}

	/**
	 * @param string $riskScoreToken
	 * @param string $siteKey
	 * @param UserIdentity $user
	 * @param int[] $localBlockIds
	 * @param int[] $globalBlockIds
	 * @param string $pageViewId
	 */
	private function dispatchRiskScore(
		string $riskScoreToken,
		string $siteKey,
		UserIdentity $user,
		array $localBlockIds,
		array $globalBlockIds,
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
			$this->logger->error(
				'hCaptcha siteverify failed when collecting risk score for blocked user.',
				[ 'user' => $user->getName() ]
			);

			return $this->newNoContentResponse();
		}

		$hookRunner = new HookRunner( $this->hookContainer );
		$hookRunner->onConfirmEditHCaptchaRiskScoreRetrievedForBlocks(
			$riskScore,
			$localBlockIds,
			$globalBlockIds,
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

	/**
	 * @return array{local: int[], global: int[]}|null
	 */
	private function resolveBlockIds( Authority $authority, string $page ): ?array {
		$title = $this->titleFactory->newFromText( $page );
		if ( !$title ) {
			return null;
		}

		$blocks = $this->blocksLookup->getBlocksRequiringHCaptcha( $title, $authority->getBlock() );
		$localBlockIds = $this->blocksLookup->listBlockIds( $blocks['local'] );
		$globalBlockIds = $this->blocksLookup->listBlockIds( $blocks['global'] );
		if ( !$localBlockIds && !$globalBlockIds ) {
			return null;
		}

		return [ 'local' => $localBlockIds, 'global' => $globalBlockIds ];
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
