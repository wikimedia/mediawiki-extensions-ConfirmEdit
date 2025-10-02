const config = require( './config.json' );
const wiki = mw.config.get( 'wgDBname' );

/**
 * Conclude and emit a performance measurement in seconds via mw.track.
 *
 * @param {string} topic Unique name for the measurement to be sent to mw.track().
 * @param {string} startName Name of the performance mark denoting the start of the measurement.
 * @param {string} endName Name of the performance mark denoting the end of the measurement.
 */
function trackPerformanceTiming( topic, startName, endName ) {
	performance.mark( endName );

	const { duration } = performance.measure( topic, startName, endName );

	if ( mw.config.get( 'wgCanonicalSpecialPageName' ) === 'CreateAccount' ) {
		mw.track( 'specialCreateAccount.performanceTiming', topic, duration / 1000 );

		// Possible metric names used here:
		// * mediawiki_special_createaccount_hcaptcha_load_duration_seconds
		// * mediawiki_special_createaccount_hcaptcha_execute_duration_seconds
		// NOTE: while the metric value is in milliseconds, the statsd handler in WikimediaEvents
		// will handle unit conversion.
		mw.track(
			`stats.mediawiki_special_createaccount_${ topic.replace( /-/g, '_' ) }_duration_seconds`,
			duration,
			{ wiki: wiki }
		);
	}
}

/**
 * Load the hCaptcha script.
 *
 * This method does not execute hCaptcha unless hCaptcha is configured to run when loaded.
 * For example, when hCaptcha is loaded with render=explicit the caller should explicitly
 * render hCaptcha with a win.hcaptcha.render() call.
 *
 * @param {Window} win
 * @param {Object.<string, string>} apiUrlQueryParameters Query parameters to append to the API URL
 *   For example, `{ 'render' => 'explicit' }` when always wanting to render explicitly.
 * @return {Promise<void>} A promise that resolves when hCaptcha loads and
 *   rejects if hCaptcha does not load
 */
const loadHCaptcha = ( win, apiUrlQueryParameters = {} ) => new Promise( ( resolve, reject ) => {
	performance.mark( 'hcaptcha-load-start' );

	// NOTE: Use hCaptcha's onload parameter rather than the return value of getScript()
	// to run init code, as the latter would run it too early and use
	// a potentially inconsistent config.
	win.onHCaptchaSDKLoaded = function () {
		trackPerformanceTiming(
			'hcaptcha-load',
			'hcaptcha-load-start',
			'hcaptcha-load-complete'
		);

		resolve();
	};

	const hCaptchaApiUrl = new URL( config.HCaptchaApiUrl );

	for ( const [ name, value ] of Object.entries( apiUrlQueryParameters ) ) {
		hCaptchaApiUrl.searchParams.set( name, value );
	}

	hCaptchaApiUrl.searchParams.set( 'onload', 'onHCaptchaSDKLoaded' );

	const script = document.createElement( 'script' );
	script.src = hCaptchaApiUrl.toString();
	script.async = true;
	if ( config.HCaptchaApiUrlIntegrityHash ) {
		script.integrity = config.HCaptchaApiUrlIntegrityHash;
		script.crossOrigin = 'anonymous';
	}

	script.onerror = () => {
		trackPerformanceTiming(
			'hcaptcha-load',
			'hcaptcha-load-start',
			'hcaptcha-load-complete'
		);

		mw.track( 'stats.mediawiki_confirmedit_hcaptcha_script_error_total', 1, {
			wiki: wiki
		} );
		mw.errorLogger.logError(
			new Error( 'Unable to load hCaptcha script' ),
			'error.confirmedit'
		);

		reject( 'generic-error' );
	};

	document.head.appendChild( script );
} );

/**
 * Trigger a single hCaptcha workflow execution asynchronously. This may cause a challenge
 * to the user that will interrupt any user flow, so should not be run unless the user
 * has taken an action to submit the form.
 *
 * A promise will be returned that will be rejected if the hCaptcha execution failed
 * and resolved if the hCaptcha execution succeeded. If the hCaptcha execution succeeds
 * then the h-captcha-response token will be returned as the value. On failure, the
 * value will be the associated error.
 *
 * @param {Window} win The window object, which can be changed for testing purposes
 * @param {string} captchaId The ID of the hCaptcha instance which has
 *   been rendered by `hcaptcha.render`
 * @return {Promise<string>} A promise that resolves if hCaptcha failed to initialize,
 * or after the first time the user attempts to submit the form and hCaptcha finishes running.
 */
const executeHCaptcha = ( win, captchaId ) => new Promise( ( resolve, reject ) => {
	try {
		performance.mark( 'hcaptcha-execute-start' );

		try {
			mw.track( 'stats.mediawiki_confirmedit_hcaptcha_execute_total', 1, {
				wiki: wiki
			} );
			win.hcaptcha.execute( captchaId, { async: true } )
				.then( ( { response } ) => {
					mw.track( 'stats.mediawiki_confirmedit_hcaptcha_form_submit_total', 1, {
						wiki: wiki
					} );
					resolve( response );
				} )
				.catch( ( error ) => {
					reject( error );
				} );
		} finally {
			trackPerformanceTiming(
				'hcaptcha-execute',
				'hcaptcha-execute-start',
				'hcaptcha-execute-complete'
			);
		}
	} catch ( error ) {
		mw.errorLogger.logError( new Error( error ), 'error.confirmedit' );
		mw.track(
			'stats.mediawiki_confirmedit_hcaptcha_execute_workflow_error_total', 1, {
				code: error.replace( /-/g, '_' ),
				wiki: wiki
			}
		);
		reject( error );
	}
} );

module.exports = {
	trackPerformanceTiming: trackPerformanceTiming,
	loadHCaptcha: loadHCaptcha,
	executeHCaptcha: executeHCaptcha
};
