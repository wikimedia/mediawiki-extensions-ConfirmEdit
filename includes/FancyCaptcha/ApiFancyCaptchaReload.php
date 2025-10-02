<?php

namespace MediaWiki\Extension\ConfirmEdit\FancyCaptcha;

use MediaWiki\Api\ApiBase;

/**
 * Api module to reload FancyCaptcha
 *
 * @ingroup API
 * @ingroup Extensions
 */
class ApiFancyCaptchaReload extends ApiBase {
	public function execute() {
		$result = $this->getResult();

		if ( $this->getUser()->pingLimiter( 'badcaptcha', 1 ) ) {
			$result->addValue( null, $this->getModuleName(), [ 'index' => 0 ] );
			return;
		}

		# Get a new FancyCaptcha form data
		$captcha = new FancyCaptcha();
		$info = $captcha->getCaptcha();
		$captchaIndex = $captcha->storeCaptcha( $info );

		$result->addValue( null, $this->getModuleName(), [ 'index' => $captchaIndex ] );
	}

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore Merely declarative
	 */
	public function isInternal() {
		return true;
	}

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore Merely declarative
	 */
	public function getAllowedParams() {
		return [];
	}

	/**
	 * @see ApiBase::getExamplesMessages()
	 * @return array
	 * @codeCoverageIgnore Merely declarative
	 */
	protected function getExamplesMessages() {
		return [
			'action=fancycaptchareload'
				=> 'apihelp-fancycaptchareload-example-1',
		];
	}
}
