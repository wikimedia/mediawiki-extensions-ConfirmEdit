<?php

namespace MediaWiki\Extension\ConfirmEdit\hCaptcha\Services;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Html\Html;
use MediaWiki\Output\OutputPage;

/**
 * Service used to de-duplicate the code for adding hCaptcha to the output in both
 * {@link HCaptcha} and {@link HTMLHCaptchaField}.
 */
class HCaptchaOutput {

	/** @internal Only public for service wiring use. */
	public const CONSTRUCTOR_OPTIONS = [
		'HCaptchaSiteKey',
		'HCaptchaEnterprise',
		'HCaptchaSecureEnclave',
		'HCaptchaInvisibleMode',
		'HCaptchaApiUrl',
	];

	private ServiceOptions $options;

	public function __construct( ServiceOptions $options ) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->options = $options;
	}

	/**
	 * Returns the HTML needed to add HCaptcha to a form.
	 *
	 * This method also adds other required script tags and ResourceLoader modules to the provided OutputPage.
	 *
	 * @param OutputPage $outputPage
	 * @param bool $previouslyFailedHCaptcha Whether the user has failed HCaptcha for a previous attempt at
	 *   submitting the associated form.
	 * @return string HTML of the hCaptcha fields to append to the outputted HTML
	 */
	public function addHCaptchaToForm( OutputPage $outputPage, bool $previouslyFailedHCaptcha ): string {
		$siteKey = $this->options->get( 'HCaptchaSiteKey' );
		$useInvisibleMode = $this->options->get( 'HCaptchaInvisibleMode' );

		$hCaptchaElementAttribs = [
			'id' => 'h-captcha',
			'class' => [
				'h-captcha',
				'mw-confirmedit-captcha-fail' => $previouslyFailedHCaptcha,
			],
			'data-sitekey' => $siteKey,
		];
		if ( $useInvisibleMode ) {
			$hCaptchaElementAttribs['data-size'] = 'invisible';
		}
		$output = Html::element( 'div', $hCaptchaElementAttribs );

		$useSecureEnclave = $this->options->get( 'HCaptchaEnterprise' ) &&
			$this->options->get( 'HCaptchaSecureEnclave' );
		if ( !$useSecureEnclave ) {
			$hCaptchaApiUrl = $this->options->get( 'HCaptchaApiUrl' );
			$outputPage->addHeadItem(
				'h-captcha',
				Html::element( 'script', [ 'src' => $hCaptchaApiUrl, 'async', 'defer' ] )
			);
		}

		// Load the hCaptcha module if we are adding the hCaptcha field. This will handle the secure enclave mode if
		// it is enabled.
		$outputPage->addModules( 'ext.confirmEdit.hCaptcha' );
		$outputPage->addJsConfigVars( 'hCaptchaApiUrl', $this->options->get( 'HCaptchaApiUrl' ) );
		$outputPage->addJsConfigVars( 'hCaptchaUseSecureEnclave', $useSecureEnclave );

		if ( $useInvisibleMode ) {
			$output .= $outputPage->msg( 'hcaptcha-privacy-policy' )->parse();
		}

		return $output;
	}
}
