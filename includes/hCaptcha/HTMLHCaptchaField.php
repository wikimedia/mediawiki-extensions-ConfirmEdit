<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\hCaptcha;

use MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\HCaptchaOutput;
use MediaWiki\HTMLForm\HTMLFormField;
use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;

class HTMLHCaptchaField extends HTMLFormField {
	/** @var string Error returned by hCaptcha in the previous round. */
	protected $error;

	/** @var bool On login, defer a missing token to AuthManager instead of failing here. */
	private bool $deferMissingTokenToAuth;

	/**
	 * Parameters:
	 * - key: (string, required) Public key
	 * - error: (string) Error from the previous captcha round
	 * - deferMissingTokenToAuth: (bool) On login, let a missing token fall through to AuthManager
	 * @param array $params
	 */
	public function __construct( array $params ) {
		$params += [ 'error' => null, 'deferMissingTokenToAuth' => false ];
		parent::__construct( $params );

		$this->error = $params['error'];
		$this->deferMissingTokenToAuth = $params['deferMissingTokenToAuth'];

		$this->mName = 'h-captcha-response';
	}

	/**
	 * On login, defer a missing token to AuthManager, which surfaces a clear "additional
	 * verification required" message; elsewhere keep the field-level error (T428892).
	 * @inheritDoc
	 */
	public function validate( $value, $alldata ): bool|Message|string {
		if ( !$value ) {
			return $this->deferMissingTokenToAuth ? true : $this->msg( 'hcaptcha-missing-token' );
		}

		return parent::validate( $value, $alldata );
	}

	/** @inheritDoc */
	public function getInputHTML( $value ) {
		$out = $this->mParent->getOutput();

		/** @var HCaptchaOutput $output */
		$output = MediaWikiServices::getInstance()->get( 'HCaptchaOutput' )
			->addHCaptchaToForm( $out, (bool)$this->error );
		HCaptcha::addCSPSources( $out->getCSP() );

		return $output;
	}
}
