/* global hcaptcha:false */

const config = require( './config.json' );

function useSecureEnclave() {
	mw.loader.getScript( config.HCaptchaApiUrl )
		.then( () => {
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
		} )
		.catch( () => {
			mw.errorLogger.logError(
				new Error( 'Unable to load hCaptcha script in secure enclave mode' ),
				'error.confirmedit'
			);
		} );
}

module.exports = useSecureEnclave;
