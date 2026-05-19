/**
 * Grade C hCaptcha bundle entry point. ES5.
 */
( function ( window ) {
	'use strict';

	// config.json is registered at runtime so the same bundle can serve any
	// wiki — utils.js does require('./config.json').
	__defineModule( './config.json', function ( module ) {
		module.exports = ( window.__confirmEditHCaptchaGradeC || {} ).configModule || {};
	} );

	$( function () {
		try {
			__require( './secureEnclave.js' )( window ).catch( function ( error ) {
				window.mw.errorLogger.logError( error, 'error.confirmedit' );
			} );
		} catch ( error ) {
			window.mw.errorLogger.logError( error, 'error.confirmedit' );
		}
	} );
}( window ) );
