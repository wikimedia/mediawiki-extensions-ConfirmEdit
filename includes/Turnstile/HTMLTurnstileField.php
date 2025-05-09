<?php

namespace MediaWiki\Extension\ConfirmEdit\Turnstile;

use MediaWiki\Html\Html;
use MediaWiki\HTMLForm\HTMLFormField;

/**
 * Creates a Turnstile widget. Does not return any data; handling the data submitted by the
 * widget is callers' responsibility.
 */
class HTMLTurnstileField extends HTMLFormField {
	/** @var string Public key parameter to be passed to Turnstile. */
	protected $key;

	/** @var string Error returned by Turnstile in the previous round. */
	protected $error;

	/**
	 * Parameters:
	 * - key: (string, required) Turnstile public key
	 * - error: (string) Turnstile error from the previous round
	 * @param array $params
	 */
	public function __construct( array $params ) {
		$params += [ 'error' => null ];
		parent::__construct( $params );

		$this->key = $params['key'];
		$this->error = $params['error'];

		$this->mName = 'cf-turnstile-response';
	}

	/** @inheritDoc */
	public function getInputHTML( $value ) {
		$out = $this->mParent->getOutput();
		$lang = htmlspecialchars( urlencode( $this->mParent->getLanguage()->getCode() ) );

		// Insert the Turnstile script, in display language, if available.
		// Language falls back to the browser's display language.
		// See https://developers.cloudflare.com/turnstile/reference/supported-languages/
		$out->addHeadItem(
			'cf-turnstile-script',
			"<script src=\"https://challenges.cloudflare.com/turnstile/v0/api.js?language={$lang}\" async defer>
			</script>"
		);
		Turnstile::addCSPSources( $out->getCSP() );

		return Html::element( 'div', [
			'class' => [
				'cf-turnstile',
				'mw-confirmedit-captcha-fail' => (bool)$this->error,
			],
			'data-sitekey' => $this->key,
		] );
	}
}
