<?php
/**
 * @author strix
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}

$dir = __DIR__;
require_once $dir . '/AreYouAHuman/ayah.php';

class AyahCaptcha extends SimpleCaptcha {
	/**
	* Determines if the Catpcha was a pass.
	*
	* @return {bool} True if the Captcha was a pass
	*/
	function passCaptcha() {
		$ayah = new AYAH();
		return $ayah->scoreResult();
	}

        /* Suppress redundant generation of SimpleCaptcha itself */
	function addCaptchaAPI( &$resultArr ) {
	}

	/**
	* Gets the HTML that will be displayed on a form.
	*
	* @return {string} HTML
	*/
	function getForm() {
		$ayah = new AYAH();
		return $ayah->getPublisherHTML() . '<noscript>' . wfMessage( 'ayahcaptcha-nojs' )->parse() . '</noscript>';
	}

	/**
	* Gets a localized string
	*
	* @param {string} $action Action being taken
	*
	* @return {string} Localized string
	*/
	function getMessage( $action ) {
		$name = 'ayahcaptcha-' . $action;
		$text = wfMessage( $name )->text();
		# Obtain a more tailored message, if possible, otherwise, fall back to
		# the default for edits
		return wfMessage( $name, $text )->isDisabled() ? wfMessage( 'ayahcaptcha-edit' )->text() : $text;
	}

	/**
	* Adds a help message to the output
	*/
	function showHelp() {
		global $wgOut;
		$wgOut->setPageTitle( wfMessage( 'captchahelp-title' )->text() );
		$wgOut->addWikiMsg( 'ayahcaptchahelp-text' );
	}
}
