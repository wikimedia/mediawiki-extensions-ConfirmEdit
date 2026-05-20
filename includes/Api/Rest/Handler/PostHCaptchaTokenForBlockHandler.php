<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Api\Rest\Handler;

use BadMethodCallException;
use MediaWiki\Config\Config;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\HCaptcha;
use MediaWiki\Extension\ConfirmEdit\Hooks\HookRunner;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Permissions\Authority;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\User\UserFactory;
use Psr\Log\LoggerInterface;
use Wikimedia\ParamValidator\ParamValidator;

class PostHCaptchaTokenForBlockHandler extends SimpleHandler {

	private LoggerInterface $logger;
	private ?HCaptcha $hCaptcha = null;

	public function __construct(
		private readonly Config $config,
		private readonly HookContainer $hookContainer,
		private readonly UserFactory $userFactory,
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
		$localBlockIds = $body['localBlockIds'] ?? [];
		$globalBlockIds = $body['globalBlockIds'] ?? [];
		$riskScoreToken = $body['riskScoreToken'] ?? '';
		if ( !$riskScoreToken || ( !$localBlockIds && !$globalBlockIds ) ) {
			$this->logger->warning(
				'hCaptcha block token request received with missing required params.',
				[
					'hasRiskScoreToken' => ( $riskScoreToken !== '' ),
					'hasLocalBlockIds' => ( count( $localBlockIds ) > 0 ),
					'hasGlobalBlockIds' => ( count( $globalBlockIds ) > 0 ),
				]
			);
			$response = $this->getResponseFactory()->create();
			$response->setStatus( 204 );
			return $response;
		}

		$siteKey = $this->config->get( 'HCaptchaBlockedIpEditingScoreCollectionSiteKey' );
		if ( !$siteKey ) {
			$this->logger->info(
				'hCaptcha block token request received but no sitekey is configured.',
				[
					'hasRiskScoreToken' => true,
					'hasLocalBlockIds' => count( $localBlockIds ) > 0,
					'hasGlobalBlockIds' => count( $globalBlockIds ) > 0,
				]
			);
			$response = $this->getResponseFactory()->create();
			$response->setStatus( 204 );
			return $response;
		}

		$request = RequestContext::getMain()->getRequest();
		$user = $authority->getUser();
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
			$response = $this->getResponseFactory()->create();
			$response->setStatus( 204 );
			return $response;
		}

		$hookRunner = new HookRunner( $this->hookContainer );
		$hookRunner->onConfirmEditHCaptchaRiskScoreRetrievedForBlocks(
			$riskScore,
			$localBlockIds,
			$globalBlockIds,
			$user,
			trim( $body['pageViewId'] ?? '' ),
			$request
		);

		$response = $this->getResponseFactory()->create();
		$response->setStatus( 204 );

		return $response;
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
			'localBlockIds' => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_REQUIRED => false,
			],
			'globalBlockIds' => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_ISMULTI => true,
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
