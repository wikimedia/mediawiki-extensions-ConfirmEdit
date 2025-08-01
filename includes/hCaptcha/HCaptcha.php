<?php

namespace MediaWiki\Extension\ConfirmEdit\hCaptcha;

use MediaWiki\Api\ApiBase;
use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Config\Config;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\ConfirmEdit\Auth\CaptchaAuthenticationRequest;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\HCaptchaOutput;
use MediaWiki\Extension\ConfirmEdit\Hooks;
use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
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

class HCaptcha extends SimpleCaptcha {
	/**
	 * @var string used for hcaptcha-edit, hcaptcha-addurl, hcaptcha-badlogin, hcaptcha-createaccount,
	 * hcaptcha-create, hcaptcha-sendemail via getMessage()
	 */
	protected static $messagePrefix = 'hcaptcha';

	/** @var string|null */
	private $error = null;

	private Config $hCaptchaConfig;
	private HCaptchaOutput $hCaptchaOutput;

	public function __construct() {
		$services = MediaWikiServices::getInstance();
		$this->hCaptchaConfig = $services->getMainConfig();
		$this->hCaptchaOutput = $services->get( 'HCaptchaOutput' );
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

	/**
	 * @param Status|array|string $info
	 */
	protected function logCheckError( $info ) {
		if ( $info instanceof Status ) {
			$errors = $info->getErrorsArray();
			$error = $errors[0][0];
		} elseif ( is_array( $info ) ) {
			$error = implode( ',', $info );
		} else {
			$error = $info;
		}

		\wfDebugLog( 'captcha', 'Unable to validate response: ' . $error );
	}

	/** @inheritDoc */
	protected function getCaptchaParamsFromRequest( WebRequest $request ) {
		$response = $request->getVal(
			'h-captcha-response',
			$request->getVal( 'captchaWord', $request->getVal( 'captchaword' ) )
		);
		return [ '', $response ];
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
		$data = [
			'secret' => $this->hCaptchaConfig->get( 'HCaptchaSecretKey' ),
			'response' => $token,
		];
		if ( $this->hCaptchaConfig->get( 'HCaptchaSendRemoteIP' ) ) {
			$webRequest = RequestContext::getMain()->getRequest();
			$data['remoteip'] = $webRequest->getIP();
		}

		$options = [
			'method' => 'POST',
			'postData' => $data,
		];

		$proxy = $this->hCaptchaConfig->get( 'HCaptchaProxy' );
		if ( $proxy ) {
			$options['proxy'] = $proxy;
		}

		$request = MediaWikiServices::getInstance()->getHttpRequestFactory()
			->create( $this->hCaptchaConfig->get( 'HCaptchaVerifyUrl' ), $options, __METHOD__ );

		$status = $request->execute();
		if ( !$status->isOK() ) {
			$this->error = 'http';
			$this->logCheckError( $status );
			return false;
		}
		$json = FormatJson::decode( $request->getContent(), true );
		if ( !$json ) {
			$this->error = 'json';
			$this->logCheckError( $this->error );
			return false;
		}
		if ( isset( $json['error-codes'] ) ) {
			$this->error = 'hcaptcha-api';
			$this->logCheckError( $json['error-codes'] );
			return false;
		}

		$debugLogContext = [
			'event' => 'captcha.solve',
			'user' => $user->getName(),
			'hcaptcha_success' => $json['success'],
		];
		if ( $this->hCaptchaConfig->get( 'HCaptchaDeveloperMode' ) ) {
			$debugLogContext = array_merge( [
				'hcaptcha_score' => $json['score'] ?? null,
				'hcaptcha_score_reason' => $json['score_reason'] ?? null,
				'hcaptcha_blob' => $json,
			], $debugLogContext );
		}
		LoggerFactory::getInstance( 'captcha' )
			->debug( 'Captcha solution attempt for {user}', $debugLogContext );

		if ( $this->hCaptchaConfig->get( 'HCaptchaDeveloperMode' )
			|| $this->hCaptchaConfig->get( 'HCaptchaUseRiskScore' ) ) {
			// T398333
			$this->storeSessionScore( 'hCaptcha-score', $json['score'] ?? null );
		}
		return $json['success'];
	}

	/** @inheritDoc */
	protected function addCaptchaAPI( &$resultArr ) {
		$resultArr['captcha'] = $this->describeCaptchaType();
		$resultArr['captcha']['error'] = $this->error;
	}

	/** @inheritDoc */
	public function describeCaptchaType() {
		return [
			'type' => 'hcaptcha',
			'mime' => 'application/javascript',
			'key' => $this->hCaptchaConfig->get( 'HCaptchaSiteKey' ),
		];
	}

	/** @inheritDoc */
	public function getMessage( $action ) {
		$msg = parent::getMessage( $action );
		if ( $this->error ) {
			$msg = new RawMessage( '<div class="error">$1</div>', [ $msg ] );
		}
		return $msg;
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
	 * Store risk score in global session
	 * @param string $sessionKey
	 * @param mixed $score
	 * @return void
	 */
	public function storeSessionScore( $sessionKey, $score ) {
		SessionManager::getGlobalSession()->set( $sessionKey, $score );
	}

	/**
	 * Retrieve session score from global session
	 * @param string $sessionKey
	 * @return mixed
	 */
	public function retrieveSessionScore( $sessionKey ) {
		return SessionManager::getGlobalSession()->get( $sessionKey );
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
