<?php

namespace MediaWiki\Extension\ConfirmEdit\hCaptcha;

use MediaWiki\Api\ApiBase;
use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Config\Config;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\ConfirmEdit\Auth\CaptchaAuthenticationRequest;
use MediaWiki\Extension\ConfirmEdit\CaptchaTriggers;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\HCaptchaEnterpriseHealthChecker;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\HCaptchaOutput;
use MediaWiki\Extension\ConfirmEdit\Hooks;
use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
use MediaWiki\Json\FormatJson;
use MediaWiki\Language\RawMessage;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Output\OutputPage;
use MediaWiki\Page\WikiPage;
use MediaWiki\Request\ContentSecurityPolicy;
use MediaWiki\Request\WebRequest;
use MediaWiki\Session\SessionManager;
use MediaWiki\Status\Status;
use MediaWiki\User\UserIdentity;
use Psr\Log\LoggerInterface;
use Wikimedia\ObjectCache\BagOStuff;
use Wikimedia\Stats\StatsFactory;

class HCaptcha extends SimpleCaptcha {
	/**
	 * @var string used for hcaptcha-edit, hcaptcha-addurl, hcaptcha-badlogin, hcaptcha-createaccount,
	 * hcaptcha-create, hcaptcha-sendemail via getMessage()
	 */
	protected static $messagePrefix = 'hcaptcha';

	/** @var string|null */
	private $error = null;

	private ?bool $result = null;

	private Config $hCaptchaConfig;
	private HCaptchaOutput $hCaptchaOutput;
	private StatsFactory $statsFactory;
	private LoggerInterface $logger;
	private HCaptchaEnterpriseHealthChecker $healthChecker;
	private BagOStuff $scoreCache;

	private const SCORE_CACHE_TTL = 300;

	public function __construct() {
		$services = MediaWikiServices::getInstance();
		$this->hCaptchaConfig = $services->getMainConfig();
		$this->hCaptchaOutput = $services->get( 'HCaptchaOutput' );
		$this->statsFactory = $services->getStatsFactory();
		$this->healthChecker = $services->get( 'HCaptchaEnterpriseHealthChecker' );
		$this->logger = LoggerFactory::getInstance( 'captcha' );
		$this->scoreCache = $services->getObjectCacheFactory()->getLocalClusterInstance();
	}

	/** @inheritDoc */
	public function getFormInformation( $tabIndex = 1, ?OutputPage $out = null ) {
		if ( $out === null ) {
			$out = RequestContext::getMain()->getOutput();
		}

		return [
			'html' => $this->hCaptchaOutput->addHCaptchaToForm( $out, (bool)$this->error ),
		];
	}

	/** @inheritDoc */
	public static function getCSPUrls() {
		return RequestContext::getMain()->getConfig()->get( 'HCaptchaCSPRules' );
	}

	/** @inheritDoc */
	public static function addCSPSources( ContentSecurityPolicy $csp ) {
		foreach ( static::getCSPUrls() as $src ) {
			// Since frame-src is not supported
			$csp->addDefaultSrc( $src );
			$csp->addScriptSrc( $src );
			$csp->addStyleSrc( $src );
		}
	}

	protected function logCheckError( Status|array|string $info, UserIdentity $userIdentity ): void {
		if ( $info instanceof Status ) {
			$errors = $info->getErrorsArray();
			$error = $errors[0][0];
		} elseif ( is_array( $info ) ) {
			$error = implode( ',', $info );
		} else {
			$error = $info;
		}

		$this->logger->error( 'Unable to validate response. Error: {error}', [
			'error' => $error,
			'user' => $userIdentity->getName(),
			'captcha_type' => self::$messagePrefix,
			'captcha_action' => $this->action ?? '-',
			'captcha_trigger' => $this->trigger ?? '-',
		] + RequestContext::getMain()->getRequest()->getSecurityLogContext( $userIdentity ) );
	}

	/** @inheritDoc */
	protected function getCaptchaParamsFromRequest( WebRequest $request ) {
		$response = $request->getVal(
			'h-captcha-response',
			$request->getVal( 'captchaWord', $request->getVal( 'captchaword' ) )
		);
		return [ '', $response ];
	}

	/** @inheritDoc */
	public function shouldCheck( WikiPage $page, $content, $section, $context, $oldtext = null ) {
		// If the "showcaptcha" consequence has been invoked by AbuseFilter, and we have
		// already attempted to verify the token, return early. This ensures that we're
		// only going to verify the token once, because shouldCheck is invoked once in the EditFilterMergedContent
		// hook by AbuseFilter, and once by ConfirmEdit
		if ( $context->getRequest()->getVal( 'wgConfirmEditForceShowCaptcha' ) &&
			$this->result !== null ) {
			return false;
		}
		return parent::shouldCheck( $page, $content, $section, $context, $oldtext );
	}

	/**
	 * Check, if the user solved the captcha.
	 *
	 * Based on reference implementation:
	 * https://github.com/google/recaptcha#php and https://docs.hcaptcha.com/
	 *
	 * @param mixed $_ Not used
	 * @param string $token token from the POST data
	 * @param UserIdentity $user
	 * @return bool
	 */
	protected function passCaptcha( $_, $token, $user ) {
		$webRequest = RequestContext::getMain()->getRequest();
		// If we have a result, "showcaptcha" consequence has been invoked, but the submission
		// is in the context of a request where the user wasn't yet required to complete a CAPTCHA,
		// then return false to avoid making a duplicate API request, and to ensure that the user
		// has to complete the "always challenge" CAPTCHA.
		if ( $this->result &&
			$this->shouldForceShowCaptcha() &&
			$webRequest->getVal( 'wgConfirmEditForceShowCaptcha' ) === null ) {
			// Set an error here, so that the page will display an appropriate
			// message for the user to resubmit the form.
			$this->error = 'forceshowcaptcha';
			return false;
		}
		$data = [
			'secret' => $this->hCaptchaConfig->get( 'HCaptchaSecretKey' ),
			'response' => $token,
		];
		$data['remoteip'] = '127.0.0.1';
		if ( $this->hCaptchaConfig->get( 'HCaptchaSendRemoteIP' ) ) {
			$data['remoteip'] = $webRequest->getIP();
		}

		$options = [
			'method' => 'POST',
			'postData' => $data,
			'timeout' => 5,
		];

		$proxy = $this->hCaptchaConfig->get( 'HCaptchaProxy' );
		if ( $proxy ) {
			$options['proxy'] = $proxy;
		}

		$request = MediaWikiServices::getInstance()->getHttpRequestFactory()
			->create( $this->hCaptchaConfig->get( 'HCaptchaVerifyUrl' ), $options, __METHOD__ );

		$timer = $this->statsFactory->withComponent( 'ConfirmEdit' )
			->getTiming( 'hcaptcha_siteverify_call' )
			->start();

		$status = $request->execute();

		$timer
			->setLabel( 'status', $status->isOK() ? 'ok' : 'failed' )
			->stop();

		if ( !$status->isOK() ) {
			$this->error = 'http';
			$this->healthChecker->incrementSiteVerifyApiErrorCount();
			$this->logCheckError( $status, $user );
			return false;
		}
		$json = FormatJson::decode( $request->getContent(), true );
		if ( !$json ) {
			$this->error = 'json';
			$this->healthChecker->incrementSiteVerifyApiErrorCount();
			$this->logCheckError( $this->error, $user );
			return false;
		}
		if ( isset( $json['error-codes'] ) ) {
			$this->error = 'hcaptcha-api';
			$this->logCheckError( $json['error-codes'], $user );
			return false;
		}

		$debugLogContext = [
			'event' => 'captcha.solve',
			'user' => $user->getName(),
			'hcaptcha_success' => $json['success'],
			'captcha_type' => self::$messagePrefix,
			'success_message' => $json['success'] ? 'Successful' : 'Failed',
			'captcha_action' => $this->action ?? '-',
			'captcha_trigger' => $this->trigger ?? '-',
			'hcaptcha_response_sitekey' => $json['sitekey'] ?? '-',
		] + $webRequest->getSecurityLogContext( $user );
		if ( $this->hCaptchaConfig->get( 'HCaptchaDeveloperMode' ) ) {
			$debugLogContext = array_merge( [
				'hcaptcha_score' => $json['score'] ?? null,
				'hcaptcha_score_reason' => $json['score_reason'] ?? null,
				'hcaptcha_blob' => $json,
			], $debugLogContext );
		}
		$this->logger->info( '{success_message} captcha solution attempt for {user}', $debugLogContext );

		if ( $this->hCaptchaConfig->get( 'HCaptchaDeveloperMode' )
			|| $this->hCaptchaConfig->get( 'HCaptchaUseRiskScore' ) ) {
			// T398333
			$this->storeSessionScore( 'hCaptcha-score', $json['score'] ?? null, $user->getName() );
		}
		$this->result = $json['success'];
		return $json['success'];
	}

	/** @inheritDoc */
	protected function addCaptchaAPI( &$resultArr ) {
		$resultArr['captcha'] = $this->describeCaptchaType( $this->action );
		$resultArr['captcha']['error'] = $this->error;
	}

	/** @inheritDoc */
	public function describeCaptchaType( ?string $action = null ) {
		return [
			'type' => 'hcaptcha',
			'mime' => 'application/javascript',
			'key' => $this->getConfig()['HCaptchaSiteKey'] ?? $this->hCaptchaConfig->get( 'HCaptchaSiteKey' ),
		];
	}

	/** @inheritDoc */
	public function getMessage( $action ) {
		if ( $this->error ) {
			if ( $this->shouldForceShowCaptcha() &&
				in_array( $action, [ CaptchaTriggers::EDIT, CaptchaTriggers::CREATE ] ) ) {
				$msg = wfMessage( 'hcaptcha-force-show-captcha-edit' );
			} else {
				$msg = parent::getMessage( $action );
			}
			return new RawMessage( '<div class="error">$1</div>', [ $msg ] );
		}

		// For edit action, hide the prompt if there's no error
		if ( $action === CaptchaTriggers::EDIT ) {
			return new RawMessage( '' );
		}

		return parent::getMessage( $action );
	}

	/** @inheritDoc */
	protected function getConfirmEditMergedFatalStatusMessageKey(): null|string {
		if ( !$this->shouldForceShowCaptcha() ) {
			return parent::getConfirmEditMergedFatalStatusMessageKey();
		}

		// If the hCaptcha captcha is being force shown, then this may cause the user to have to submit the form
		// twice (so that the second attempt always shows a challenge). In this case, we need a clear message
		// to show that the user needs to press submit again.
		return 'hcaptcha-force-show-captcha-edit';
	}

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore Merely declarative
	 */
	public function apiGetAllowedParams( ApiBase $module, &$params, $flags ) {
		return true;
	}

	/** @inheritDoc */
	public function getError() {
		return $this->error;
	}

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore Merely declarative
	 */
	public function storeCaptcha( $info ) {
		// hCaptcha is stored externally, the ID will be generated at that time as well, and
		// the one returned here won't be used. Just pretend this worked.
		return 'not used';
	}

	/**
	 * Store risk score in global session. If a username is provided, the score will be
	 * also recorded in the cache, so that any jobs can read from it.
	 * @param string $sessionKey
	 * @param mixed $score
	 * @param string|null $userName
	 * @return void
	 */
	public function storeSessionScore( $sessionKey, $score, $userName = null ) {
		SessionManager::getGlobalSession()->set( $sessionKey, $score );
		if ( $userName !== null ) {
			$this->scoreCache->set(
				$this->getScoreCacheKey( $sessionKey, $userName ),
				$score,
				self::SCORE_CACHE_TTL
			);
		}
	}

	/**
	 * Retrieve session score from global session. If a username is provided, it will attempt
	 * to read the score from the cache, if it's not present in the session.
	 *
	 * @stable to call - This may be used by code not visible in codesearch
	 * @param string $sessionKey
	 * @param string|null $userName
	 * @return mixed
	 */
	public function retrieveSessionScore( $sessionKey, $userName = null ) {
		$score = SessionManager::getGlobalSession()->get( $sessionKey );
		if ( $score !== null ) {
			return $score;
		}
		if ( $userName !== null ) {
			return $this->scoreCache->get( $this->getScoreCacheKey( $sessionKey, $userName ) );
		}
		return null;
	}

	private function getScoreCacheKey( string $sessionKey, string $userName ): string {
		return $this->scoreCache->makeGlobalKey( 'hcaptcha-score', $sessionKey . '-' . $userName );
	}

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore Merely declarative
	 */
	public function retrieveCaptcha( $index ) {
		// Just pretend it worked
		return [ 'index' => $index ];
	}

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore Merely declarative
	 */
	public function getCaptcha() {
		// hCaptcha is handled by frontend code, and an external provider; nothing to do here.
		return [];
	}

	/**
	 * @return HCaptchaAuthenticationRequest
	 */
	public function createAuthenticationRequest() {
		return new HCaptchaAuthenticationRequest();
	}

	/** @inheritDoc */
	public function onAuthChangeFormFields(
		array $requests, array $fieldInfo, array &$formDescriptor, $action
	) {
		/** @var CaptchaAuthenticationRequest $req */
		$req = AuthenticationRequest::getRequestByClass(
			$requests,
			CaptchaAuthenticationRequest::class,
			true
		);
		if ( !$req ) {
			return;
		}

		// ugly way to retrieve error information
		$captcha = Hooks::getInstance( $req->getAction() );

		$formDescriptor['captchaWord'] = [
			'class' => HTMLHCaptchaField::class,
			'error' => $captcha->getError(),
		] + $formDescriptor['captchaWord'];
	}

	/** @inheritDoc */
	public function showHelp( OutputPage $out ) {
		$out->addWikiMsg( 'hcaptcha-privacy-policy' );
	}
}
