/* global hcaptcha:false */

const config = require( './config.json' );

/**
 * Load hCaptcha in Secure Enclave mode.
 */
function useSecureEnclave() {
	// NOTE: Use hCaptcha's onload parameter rather than the return value of getScript()
	// to run init code, as the latter would run it too early and use a potentially inconsistent config.
	window.onHCaptchaSDKLoaded = () => {
		const captchaID = hcaptcha.render(
			'h-captcha',
			{
				callback: function ( token ) {
					// handle hCaptcha response token
					// eslint-disable-next-line no-jquery/no-global-selector
					$( '#h-captcha-response' ).val( token );
				}
			}
		);

		hcaptcha.execute( captchaID );
	};

	const hCaptchaApiUrl = new URL( config.HCaptchaApiUrl );
	hCaptchaApiUrl.searchParams.set( 'onload', 'onHCaptchaSDKLoaded' );

	mw.loader.getScript( hCaptchaApiUrl.toString() )
		.catch( () => {
			mw.errorLogger.logError(
				new Error( 'Unable to load hCaptcha script in secure enclave mode' ),
				'error.confirmedit'
			);
		} );
}

module.exports = useSecureEnclave;
