<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Auth;

use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Auth\AuthManager;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\ConfirmEdit\CaptchaTriggers;
use MediaWiki\Extension\ConfirmEdit\Services\CaptchaFactory;
use MediaWiki\MediaWikiServices;

/**
 * Generic captcha authentication request class.
 *
 *  A captcha consists of some data stored in the session
 * (e.g. a question and its answer), an ID that references the data, and a solution.
 */
class CaptchaAuthenticationRequest extends AuthenticationRequest {

	/** @var string Captcha solution submitted by the user. */
	public $captchaWord;

	/**
	 * @param string|null $captchaId Identifier of the captcha. Used internally to remember which
	 *  captcha was used.
	 * @param array $captchaData Information about the captcha (e.g. question text; solution).
	 *  Exact semantics differ between types.
	 */
	public function __construct(
		public ?string $captchaId = null,
		public array $captchaData = [],
	) {
	}

	/** @inheritDoc */
	public function getUniqueId() {
		return 'CaptchaAuthenticationRequest';
	}

	/** @inheritDoc */
	public function loadFromSubmission( array $data ) {
		$success = parent::loadFromSubmission( $data );
		if ( $success ) {
			// The captchaId and captchaWord were set from the submission, but captchaData was not.
			/** @var CaptchaFactory $captchaFactory */
			$captchaFactory = MediaWikiServices::getInstance()->get( 'ConfirmEditCaptchaFactory' );
			$captcha = $captchaFactory->getGlobalInstanceFromAuthenticationRequest(
				$this,
				RequestContext::getMain()->getRequest()->getSession()
			);
			$data = $captcha->retrieveCaptcha( $this->captchaId );
			$success = $data !== false;
			$this->captchaData = $data ?: [];
		}
		return $success;
	}

	public function getActionForMessage(): string {
		// generic action doesn't exist, but *Captcha::getMessage will handle that
		$action = 'generic';
		switch ( $this->action ) {
			case AuthManager::ACTION_LOGIN:
				$action = CaptchaTriggers::BAD_LOGIN;
				break;
			case AuthManager::ACTION_CREATE:
				$action = CaptchaTriggers::CREATE_ACCOUNT;
				break;
		}
		return $action;
	}

	/** @inheritDoc */
	public function getFieldInfo() {
		/** @var CaptchaFactory $captchaFactory */
		$captchaFactory = MediaWikiServices::getInstance()->get( 'ConfirmEditCaptchaFactory' );
		$captcha = $captchaFactory->getGlobalInstanceFromAuthenticationRequest(
			$this,
			RequestContext::getMain()->getRequest()->getSession()
		);

		return [
			'captchaId' => [
				'type' => 'hidden',
				'value' => $this->captchaId,
				'label' => wfMessage( 'captcha-id-label' ),
				'help' => wfMessage( 'captcha-id-help' ),
			],
			'captchaInfo' => [
				'type' => 'null',
				'label' => $captcha->getMessage( $this->getActionForMessage() ),
				'value' => $captcha->getCaptchaInfo( $this->captchaData, $this->captchaId ),
				'help' => wfMessage( 'captcha-info-help' ),
			],
			'captchaWord' => [
				'type' => 'string',
				'label' => wfMessage( 'captcha-label' ),
				'help' => wfMessage( 'captcha-help' ),
			],
		];
	}

	/** @inheritDoc */
	public function getMetadata() {
		/** @var CaptchaFactory $captchaFactory */
		$captchaFactory = MediaWikiServices::getInstance()->get( 'ConfirmEditCaptchaFactory' );
		$captcha = $captchaFactory->getGlobalInstanceFromAuthenticationRequest(
			$this,
			RequestContext::getMain()->getRequest()->getSession()
		);
		return $captcha->describeCaptchaType();
	}
}
