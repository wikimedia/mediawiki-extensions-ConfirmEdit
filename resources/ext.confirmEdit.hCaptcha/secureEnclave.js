/* eslint-disable no-jquery/no-global-selector, no-undef */
function useSecureEnclave() {
	mw.loader.getScript( mw.config.get( 'hCaptchaApiUrl' ) )
		.then( () => {
			const captchaID = hcaptcha.render(
				'h-captcha',
				{
					callback: function ( token ) {
						// handle hCaptcha response token
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
