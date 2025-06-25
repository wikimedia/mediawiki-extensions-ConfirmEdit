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
		'HCaptchaPassiveMode',
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
	 * This method also adds other required script tag to the provided output page.
	 *
	 * @param OutputPage $outputPage
	 * @param bool $previouslyFailedHCaptcha Whether the user has failed HCaptcha for a previous attempt at
	 *   submitting the associated form.
	 * @return string HTML of the hCaptcha fields to append to the outputted HTML
	 */
	public function addHCaptchaToForm( OutputPage $outputPage, bool $previouslyFailedHCaptcha ): string {
		$siteKey = $this->options->get( 'HCaptchaSiteKey' );
		$output = Html::element( 'div', [
			'class' => [
				'h-captcha',
				'mw-confirmedit-captcha-fail' => $previouslyFailedHCaptcha,
			],
			'data-sitekey' => $siteKey,
		] );

		$hCaptchaApiUrl = $this->options->get( 'HCaptchaApiUrl' );
		$outputPage->addHeadItem( 'h-captcha', "<script src=\"$hCaptchaApiUrl\" async defer></script>" );

		if ( $this->options->get( 'HCaptchaPassiveMode' ) ) {
			$output .= $outputPage->msg( 'hcaptcha-privacy-policy' )->parse();
		}

		return $output;
	}
}
