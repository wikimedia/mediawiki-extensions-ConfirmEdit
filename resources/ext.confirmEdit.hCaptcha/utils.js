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

/**
 * Load the hCaptcha script.
 *
 * This method does not execute hCaptcha unless hCaptcha is configured to run when loaded.
 * For example, when hCaptcha is loaded with render=explicit the caller should explicitly
 * render hCaptcha with a win.hcaptcha.render() call.
 *
 * @param {Window} win
 * @return {Promise<void>} A promise that resolves when hCaptcha loads and
 *   rejects if hCaptcha does not load
 */
const loadHCaptcha = ( win ) => new Promise( ( resolve, reject ) => {
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
			new Error( 'Unable to load hCaptcha script in secure enclave mode' ),
			'error.confirmedit'
		);

		reject( 'generic-error' );
	};

	document.head.appendChild( script );
} );

module.exports = { trackPerformanceTiming: trackPerformanceTiming, loadHCaptcha: loadHCaptcha };
