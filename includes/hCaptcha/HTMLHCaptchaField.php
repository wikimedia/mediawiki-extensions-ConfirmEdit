<?php

namespace MediaWiki\Extension\ConfirmEdit\hCaptcha;

use MediaWiki\HTMLForm\HTMLFormField;
use MediaWiki\MediaWikiServices;

class HTMLHCaptchaField extends HTMLFormField {
	/** @var string Error returned by hCaptcha in the previous round. */
	protected $error;

	/**
	 * Parameters:
	 * - key: (string, required) Public key
	 * - error: (string) Error from the previous captcha round
	 * @param array $params
	 */
	public function __construct( array $params ) {
		$params += [ 'error' => null ];
		parent::__construct( $params );

		$this->error = $params['error'];

		$this->mName = 'h-captcha-response';
	}

	/** @inheritDoc */
	public function getInputHTML( $value ) {
		$out = $this->mParent->getOutput();

		$output = MediaWikiServices::getInstance()->get( 'HCaptchaOutput' )
			->addHCaptchaToForm( $out, (bool)$this->error );
		HCaptcha::addCSPSources( $out->getCSP() );

		return $output;
	}
}
