$( () => {
	const useSecureEnclave = require( './secureEnclave.js' );

	if ( mw.config.get( 'hCaptchaUseSecureEnclave' ) ) {
		useSecureEnclave();
	}
} );
