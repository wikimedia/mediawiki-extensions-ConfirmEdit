<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\hCaptcha;

use LogicException;
use MediaWiki\Api\ApiBase;
use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Auth\AuthManager;
use MediaWiki\Config\Config;
use MediaWiki\Context\RequestContext;
use MediaWiki\EditPage\EditPage;
use MediaWiki\Extension\ConfirmEdit\Auth\CaptchaAuthenticationRequest;
use MediaWiki\Extension\ConfirmEdit\CaptchaTriggers;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\HCaptchaEnterpriseHealthChecker;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\HCaptchaOutput;
use MediaWiki\Extension\ConfirmEdit\Services\CaptchaFactory;
use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Json\FormatJson;
use MediaWiki\Language\RawMessage;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Output\OutputPage;
use MediaWiki\Request\ContentSecurityPolicy;
use MediaWiki\Request\WebRequest;
use MediaWiki\Session\SessionManager;
use MediaWiki\Status\Status;
use MediaWiki\User\UserIdentity;
use Psr\Log\LoggerInterface;
use Wikimedia\ObjectCache\BagOStuff;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\Stats\StatsFactory;

class HCaptcha extends SimpleCaptcha {
	/**
	 * @var string used for hcaptcha-edit, hcaptcha-addurl, hcaptcha-badlogin, hcaptcha-createaccount,
	 * hcaptcha-create, hcaptcha-sendemail via getMessage()
	 */
	protected static $messagePrefix = 'hcaptcha';

	/**
	 * HTMLForm weight for the captcha field on the account creation form, placing the hCaptcha
	 * disclaimer below the "Create your account" submit button (which has a weight of 100).
	 */
	private const CREATE_ACCOUNT_DISCLAIMER_WEIGHT = 200;

	/** @var string|null */
	private $error = null;

	/** @var string|null The sitekey returned by the siteverify API for the solved captcha. */
	private ?string $solvedCaptchaSiteKey = null;

	private Config $hCaptchaConfig;
	private HttpRequestFactory $httpRequestFactory;
	private HCaptchaOutput $hCaptchaOutput;
	private StatsFactory $statsFactory;
	private LoggerInterface $logger;
	private HCaptchaEnterpriseHealthChecker $healthChecker;
	private BagOStuff $scoreCache;

	private const SCORE_CACHE_TTL = 300;

	public function __construct() {
		$services = MediaWikiServices::getInstance();
		$this->hCaptchaConfig = $services->getMainConfig();
		$this->httpRequestFactory = $services->getHttpRequestFactory();
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

	protected function logCheckError( Status|array|string $info, UserIdentity $userIdentity, string $token ): void {
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
			'hcaptcha_token' => $token,
		] + RequestContext::getMain()->getRequest()->getSecurityLogContext( $userIdentity ) );
	}

	/** @inheritDoc */
	protected function getCaptchaParamsFromRequest( WebRequest $request ) {
		$response = $request->getVal(
			'h-captcha-response',
			// This is sad, but apparently all of these are valid
			$request->getVal( 'captchaWord',
				$request->getVal( 'captchaword',
					$request->getVal( 'wpCaptchaWord' )
				)
			)
		);
		return [ '', $response ];
	}

	/**
	 * Don't render hCaptcha form field at the top of the edit form, as we want it to be only
	 * ever at the bottom of the page.
	 *
	 * The default behaviour is to render the form fields both at the top and bottom of the edit field
	 * when in a 'addurl' trigger (which causes issues because we can only have one hCaptcha widget on the page).
	 *
	 * @inheritDoc
	 */
	public function showEditFormFields( EditPage $editPage, OutputPage $out ) {
	}

	/**
	 * Returns true if passCaptcha() should reject the current submission with
	 * error='forceshowcaptcha', signaling to the JS interface that it needs to
	 * present the always-challenge widget before the edit can proceed.
	 *
	 * This is the case when a captcha was already solved in this request (passive
	 * or otherwise), an always-challenge is required, but the current request is
	 * not yet the dedicated always-challenge resubmission. The resubmission is
	 * detected by the presence of the `wgConfirmEditForceShowCaptcha` request
	 * parameter.
	 */
	private function shouldForceShowCaptchaChallenge( WebRequest $webRequest ): bool {
		$userAlreadySolvedACaptcha = $this->solvedCaptchaSiteKey !== null;

		// can be set by any extension, e.g. AbuseFilter's "showcaptcha" consequence.
		$alwaysChallengeRequired = $this->shouldForceShowCaptcha() && $this->getAlwaysChallengeSiteKey();

		$isForceChallengeResubmission = $webRequest->getVal( 'wgConfirmEditForceShowCaptcha' ) !== null &&
			$this->getAlwaysChallengeSiteKey() === $this->solvedCaptchaSiteKey;

		return $userAlreadySolvedACaptcha && $alwaysChallengeRequired && !$isForceChallengeResubmission;
	}

	/**
	 * Retrieves the risk score associated with the provided token.
	 *
	 * @param WebRequest $request
	 * @param string $token token from the POST data
	 * @param UserIdentity $user User performing the verification
	 * @param string[]|null $validKeys List of valid keys, null to use those for the current action.
	 * @return float|false The risk score for the provided token, false on failure.
	 */
	public function retrieveRiskScore(
		WebRequest $request,
		string $token,
		UserIdentity $user,
		?array $validKeys = null
	): float|false {
		$json = $this->callSiteVerify( $token, $user, $request );

		if ( $json === false ||
			!$this->hasValidKey( $request, $token, $json, $user, $validKeys ) ) {
			return false;
		}

		$this->addHCaptchaScore( $user, $json );

		return is_numeric( $json['score'] ?? null ) ?
			(float)$json['score'] :
			false;
	}

	/**
	 * Check, if the user solved the captcha.
	 *
	 * Based on reference implementation:
	 * https://github.com/google/recaptcha#php and https://docs.hcaptcha.com/
	 *
	 * @param mixed $_ Not used
	 * @param null|string $token token from the POST data
	 * @param UserIdentity $user
	 * @return bool
	 */
	protected function passCaptcha( $_, $token, $user ) {
		$this->error = null;
		$webRequest = RequestContext::getMain()->getRequest();
		if ( $this->shouldForceShowCaptchaChallenge( $webRequest ) ) {
			$this->error = 'forceshowcaptcha';
			$output = RequestContext::getMain()->getOutput();
			$output->addJsConfigVars(
				'wgHCaptchaTriggerFormSubmission',
				true
			);
			return false;
		}

		if ( $this->isCaptchaSolved() !== null ) {
			return (bool)$this->isCaptchaSolved();
		}

		$json = $this->callSiteVerify( $token, $user, $webRequest );

		if ( $json === false ) {
			return false;
		}

		// If force showing a CAPTCHA and the always challenge sitekey was not used, return the 'forceshowcaptcha'
		// error without calling ::hasValidKey to avoid logging a sitekey-mismatch error
		if (
			$this->shouldForceShowCaptcha() &&
			!in_array( $json['sitekey'] ?? null, $this->getAllowedSiteKeysForCurrentAction(), true ) &&
			in_array( $json['sitekey'] ?? null, $this->getAllSiteKeysForCurrentAction(), true )
		) {
			$this->error = 'forceshowcaptcha';
			$output = RequestContext::getMain()->getOutput();
			$output->addJsConfigVars(
				'wgHCaptchaTriggerFormSubmission',
				true
			);
			$this->setCaptchaSolved( false );
			return false;
		}

		if ( !$this->hasValidKey( $webRequest, $token, $json, $user ) ) {
			$this->setCaptchaSolved( false );
			return false;
		}

		$this->addHCaptchaScore( $user, $json );
		$this->solvedCaptchaSiteKey = $json['success'] ? ( $json['sitekey'] ?? null ) : null;
		$this->setCaptchaSolved( $json['success'] );

		return $json['success'];
	}

	/**
	 * Call the hCaptcha SiteVerify API with the given token.
	 *
	 * On success, returns the decoded JSON response array from hCaptcha.
	 * On failure, sets $this->error and returns false.
	 *
	 * @param ?string $token The hCaptcha response token to verify
	 * @param UserIdentity $user The user performing the action (used for logging)
	 * @param WebRequest $webRequest Current request
	 * @return array|false Decoded siteverify JSON response on success, false on failure
	 */
	private function callSiteVerify(
		?string $token,
		UserIdentity $user,
		WebRequest $webRequest
	): array|false {
		$this->error = null;

		if ( !$token ) {
			$this->error = 'missing-token';
			$this->logger->warning(
				'No hCaptcha token present in the request; skipping siteverify call.',
				[
					'error' => $this->error,
					'user' => $user->getName(),
					'captcha_type' => self::$messagePrefix,
					'captcha_action' => $this->action ?? '-',
					'captcha_trigger' => $this->trigger ?? '-',
				] + $webRequest->getSecurityLogContext( $user )
			);
			return false;
		}
		$requestData = $this->buildSiteVerifyRequestData( $webRequest, $token );
		$options = $requestData[ 'options' ];
		$verifyUrl = $requestData[ 'verifyUrl' ];

		$status = $this->executeSiteVerifyWithRetry( $verifyUrl, $options, $user, $token );
		if ( $status === null ) {
			return false;
		}

		return $this->parseSiteVerifyResponse( $status, $user, $token );
	}

	/**
	 * @param WebRequest $webRequest
	 * @param string $token
	 * @return array{options:array,verifyUrl:string}
	 */
	private function buildSiteVerifyRequestData( WebRequest $webRequest, string $token ): array {
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
			'timeout' => 1,
		];

		$proxy = $this->hCaptchaConfig->get( 'HCaptchaProxy' );
		if ( $proxy ) {
			$options['proxy'] = $proxy;
		}

		$verifyUrl = $this->hCaptchaConfig->get( 'HCaptchaVerifyUrl' );
		return [ 'options' => $options, 'verifyUrl' => $verifyUrl ];
	}

	/**
	 * @param string $verifyUrl
	 * @param array $options
	 * @param UserIdentity $user
	 * @param string $token
	 * @return Status|null Null if all attempts failed (caller must return false).
	 */
	private function executeSiteVerifyWithRetry(
		string $verifyUrl,
		array $options,
		UserIdentity $user,
		string $token
	): ?Status {
		// Initial attempt plus up to two retries, each preceded by a 10ms delay.
		$maxAttempts = 3;
		$attempt = 1;
		$status = $this->executeSiteVerifyRequest( $verifyUrl, $options, false );
		while ( !$status->isOK() && $attempt < $maxAttempts ) {
			$this->logger->warning(
				'SiteVerify API request failed on attempt {attempt} of {maxAttempts}, retrying. Error: {error}',
				[
					'attempt' => $attempt,
					'maxAttempts' => $maxAttempts,
					'error' => $status->getMessage()->text(),
				]
			);
			usleep( 10_000 );
			$status = $this->executeSiteVerifyRequest( $verifyUrl, $options, true );
			$attempt++;
		}

		if ( !$status->isOK() ) {
			$this->logger->error(
				'All SiteVerify API attempts failed. Error: {error}',
				[ 'error' => $status->getMessage()->text() ]
			);
			$this->statsFactory->withComponent( 'ConfirmEdit' )
				->getCounter( 'hcaptcha_siteverify_exhausted_total' )
				->increment();
			$this->error = 'http';
			$this->healthChecker->incrementSiteVerifyApiErrorCount();
			$this->logCheckError( $status, $user, $token );
			return null;
		}

		if ( $attempt > 1 ) {
			$this->logger->info(
				'SiteVerify API call succeeded on attempt {attempt} of {maxAttempts} after initial failure',
				[
					'attempt' => $attempt,
					'maxAttempts' => $maxAttempts,
				]
			);
		}
		return $status;
	}

	private function parseSiteVerifyResponse(
		Status $status,
		UserIdentity $user,
		string $token
	): array|false {
		$json = FormatJson::decode( $status->getValue(), true );
		if ( !$json ) {
			$this->error = 'json';
			$this->healthChecker->incrementSiteVerifyApiErrorCount();
			$this->logCheckError( $this->error, $user, $token );
			return false;
		}
		if ( isset( $json['error-codes'] ) ) {
			$this->error = 'hcaptcha-api';
			$this->logCheckError( $json['error-codes'], $user, $token );
			return false;
		}

		return $json;
	}

	/**
	 * Checks whether a given key is among the provided list of valid keys,
	 * logging an error if it isn't.
	 *
	 * This check is necessary to prevent client-side tampering (T410024,
	 * T410657).
	 *
	 * If no list of valid keys is provided, the key is compared against the
	 * list of keys allowed for the current action.
	 *
	 * @param WebRequest $webRequest
	 * @param null|string $token token from the POST data
	 * @param array $json POST data returned from the siteverify API
	 * @param UserIdentity $user User performing the verification
	 * @param string[]|null $validKeys List of valid keys, null to use those for the current action.
	 * @return bool
	 */
	private function hasValidKey(
		WebRequest $webRequest,
		?string $token,
		array $json,
		UserIdentity $user,
		?array $validKeys = null
	): bool {
		if ( !$json ) {
			return false;
		}

		if ( $validKeys === null ) {
			$validKeys = $this->getAllowedSiteKeysForCurrentAction();
		}

		$siteKeyUsed = $json['sitekey'] ?? null;
		if ( !in_array( $siteKeyUsed, $validKeys ) ) {
			$this->error = 'sitekey-mismatch';
			$this->logCheckError( $this->error, $user, $token );
			return false;
		}

		$debugLogContext = [
			'event' => 'captcha.solve',
			'user' => $user->getName(),
			'hcaptcha_success' => $json['success'],
			'hcaptcha_token' => $token,
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

		return true;
	}

	private function addHCaptchaScore( UserIdentity $user, array $json ): void {
		if ( $this->hCaptchaConfig->get( 'HCaptchaDeveloperMode' )
			|| $this->hCaptchaConfig->get( 'HCaptchaUseRiskScore' ) ) {
			// T398333
			$this->storeSessionScore( 'hCaptcha-score', $json['score'] ?? null, $user->getName() );
		}
	}

	/**
	 * @inheritDoc
	 *
	 * If the "showcaptcha" consequence is active and requires an always-challenge
	 * sitekey, the captcha is not considered solved unless it was solved with that
	 * specific sitekey. This prevents a normal (passive) captcha solve from
	 * satisfying the AbuseFilter always-challenge requirement.
	 */
	public function isCaptchaSolved(): ?bool {
		$solved = parent::isCaptchaSolved();
		if ( $solved && $this->shouldForceShowCaptcha() ) {
			$alwaysChallengeSiteKey = $this->getAlwaysChallengeSiteKey();
			if ( $alwaysChallengeSiteKey !== null && $this->solvedCaptchaSiteKey !== $alwaysChallengeSiteKey ) {
				return false;
			}
		}
		return $solved;
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
			'key' => $this->getSiteKeyForAction(),
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

	/** @inheritDoc */
	protected function isAPICaptchaModule( $module ) {
		return parent::isAPICaptchaModule( $module )
			|| $module->getModuleName() === 'visualeditoredit';
	}

	/** @inheritDoc */
	public function apiGetAllowedParams( ApiBase $module, &$params, $flags ) {
		parent::apiGetAllowedParams( $module, $params, $flags );

		if ( $this->isAPICaptchaModule( $module ) ) {
			$params['wgConfirmEditForceShowCaptcha'] = [
				ParamValidator::PARAM_TYPE => 'boolean',
				ApiBase::PARAM_HELP_MSG => 'confirmedit-hcaptcha-apihelp-param-forceshowcaptcha',
			];
		}

		return true;
	}

	/** @inheritDoc */
	public function getApiParams(): array {
		return array_merge( parent::getApiParams(), [ 'wgConfirmEditForceShowCaptcha' ] );
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
		/** @var CaptchaFactory $captchaFactory */
		$captchaFactory = MediaWikiServices::getInstance()->get( 'ConfirmEditCaptchaFactory' );
		$captcha = $captchaFactory->getGlobalInstanceFromAuthenticationRequest(
			$req,
			RequestContext::getMain()->getRequest()->getSession()
		);

		$formDescriptor['captchaWord'] = [
			'class' => HTMLHCaptchaField::class,
			'error' => $captcha->getError(),
		] + $formDescriptor['captchaWord'];

		// On the account creation form, move the hCaptcha disclaimer below the "Create your
		// account" button (which has a weight of 100). This is only done in invisible mode,
		// where the disclaimer is rendered inside this field (see HCaptchaOutput); in visible
		// mode this field holds a user-facing widget and the disclaimer comes from showHelp(),
		// so reordering it would move the widget rather than the disclaimer. See T428135.
		$isAccountCreation = in_array(
			$action,
			[ AuthManager::ACTION_CREATE, AuthManager::ACTION_CREATE_CONTINUE ],
			true
		);
		if ( $isAccountCreation && $this->hCaptchaConfig->get( 'HCaptchaInvisibleMode' ) ) {
			$formDescriptor['captchaWord']['weight'] = self::CREATE_ACCOUNT_DISCLAIMER_WEIGHT;
		}
	}

	/** @inheritDoc */
	public function showHelp( OutputPage $out ) {
		$out->addWikiMsg( 'hcaptcha-privacy-policy' );
	}

	/**
	 * Get the SiteKey for the instance.
	 *
	 * This returns a value from the following sources, in order of priority:
	 * - the HCaptchaAlwaysChallengeSiteKey from $wgCaptchaTriggers for the current action,
	 *   if ::shouldForceShowCaptcha mode is enabled and an "always challenge" sitekey has been set
	 * - the HCaptchaSiteKey config property from $wgCaptchaTriggers for the current action
	 * - the global $wgHCaptchaSiteKey
	 *
	 * @return string The hCaptcha SiteKey associated with this instance
	 */
	public function getSiteKeyForAction(): string {
		$siteKey = $this->getPrimarySiteKey();
		if ( $this->shouldForceShowCaptcha() ) {
			$siteKey = $this->getAlwaysChallengeSiteKey() ?? $siteKey;
		}

		return $siteKey;
	}

	/**
	 * Execute a SiteVerify API request and record timing metrics.
	 *
	 * On success, the response body is available via {@see Status::getValue()}.
	 */
	private function executeSiteVerifyRequest(
		string $verifyUrl,
		array $options,
		bool $isRetry
	): Status {
		$request = $this->httpRequestFactory->create( $verifyUrl, $options, __METHOD__ );

		$timer = $this->statsFactory->withComponent( 'ConfirmEdit' )
			->getTiming( 'hcaptcha_siteverify_call' )
			->start();

		$status = $request->execute();

		$timer
			->setLabel( 'status', $status->isOK() ? 'ok' : 'failed' )
			->setLabel( 'is_retry', $isRetry ? 'true' : 'false' )
			->stop();

		if ( $status->isOK() ) {
			$status->value = $request->getContent();
		}

		return $status;
	}

	/**
	 * Get a list of allowed SiteKeys for the current action.
	 *
	 * If Always Challenge Mode is enabled and HCaptchaAlwaysChallengeSiteKey is set,
	 * this method returns an array containing only that key. If Always Challenge
	 * Mode is enabled but HCaptchaAlwaysChallengeSiteKey is not set, this method
	 * falls back to normal mode behavior (primary key plus additional keys).
	 *
	 * Otherwise, this returns a list containing the primary SiteKey for the current
	 * action plus any additional key provided under the HCaptchaAdditionalValidSiteKeys
	 * configuration key for the current action.
	 *
	 * @return string[] A list of allowed hCaptcha SiteKeys allowed for this instance
	 */
	private function getAllowedSiteKeysForCurrentAction(): array {
		$triggerConfig = $this->getConfig();

		// In Always Challenge Mode, return only the Always Challenge SiteKey if set.
		// If not set, fallback to normal mode behavior to avoid unexpected edge cases.
		if ( $this->shouldForceShowCaptcha() ) {
			$alwaysChallengeSiteKey = $this->getAlwaysChallengeSiteKey();
			if ( $alwaysChallengeSiteKey ) {
				return [ $alwaysChallengeSiteKey ];
			}
			// Fall through to normal mode behavior
		}

		// For normal mode, return all sitekeys that are allowed for the action (including
		// the always challenge key)
		return $this->getAllSiteKeysForCurrentAction();
	}

	/**
	 * Returns the list of all possible sitekeys that are configured for the current action.
	 *
	 * To get a list of sitekeys that are considered valid for this request, then use
	 * {@link self::getAllowedSiteKeysForCurrentAction()} for that.
	 *
	 * @return string[]
	 */
	private function getAllSiteKeysForCurrentAction(): array {
		$allowedKeys = array_merge(
			[ $this->getPrimarySiteKey() ],
			[ $this->getAlwaysChallengeSiteKey() ],
			$this->getConfig()['HCaptchaAdditionalValidSiteKeys'] ?? []
		);

		return array_values( array_filter( array_unique( $allowedKeys ) ) );
	}

	/**
	 * Returns the hCaptcha primary SiteKey to be used by this instance.
	 *
	 * The primary SiteKey is either the value for HCaptchaSiteKey set in the
	 * trigger config for the current action or, if not set or empty, the HCaptchaSiteKey
	 * set in the root-level configuration.
	 *
	 * @return string
	 */
	private function getPrimarySiteKey(): string {
		$key = $this->getConfig()['HCaptchaSiteKey'] ??
			$this->hCaptchaConfig->get( 'HCaptchaSiteKey' );

		if ( $key === null ) {
			throw new LogicException( 'wgHCaptchaSiteKey is not set' );
		}

		return $key;
	}

	/**
	 * Returns the hCaptcha "always challenge" sitekey used by this instance, or `null` if no sitekey is configured.
	 *
	 * This will always return `null` if the action is {@link CaptchaTriggers::CREATE_ACCOUNT}, as that
	 * interface does not support these challenges.
	 */
	private function getAlwaysChallengeSiteKey(): ?string {
		if (
			( $this->getConfig()['HCaptchaAlwaysChallengeSiteKey'] ?? null ) &&
			$this->action !== CaptchaTriggers::CREATE_ACCOUNT
		) {
			return $this->getConfig()['HCaptchaAlwaysChallengeSiteKey'];
		}
		return null;
	}
}
