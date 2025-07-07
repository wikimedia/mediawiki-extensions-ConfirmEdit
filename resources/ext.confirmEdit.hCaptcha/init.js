$( () => {
	const useSecureEnclave = require( './secureEnclave.js' );
	const config = require( './config.json' );

	if ( config.HCaptchaEnterprise && config.HCaptchaSecureEnclave ) {
		useSecureEnclave();
	}
} );
