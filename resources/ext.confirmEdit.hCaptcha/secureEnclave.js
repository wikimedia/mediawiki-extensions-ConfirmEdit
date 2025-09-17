const config = require( './config.json' );
const ProgressIndicatorWidget = require( './ProgressIndicatorWidget.js' );
const ErrorWidget = require( './ErrorWidget.js' );

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
		duration
	);
}

/**
 * Load hCaptcha in Secure Enclave mode.
 *
 * @param {jQuery} $form The form to be protected by hCaptcha.
 * @param {jQuery} $hCaptchaField The hCaptcha input field within the form.
 * @param {Window} win
 * @return {Promise<void>} A promise that resolves if hCaptcha failed to initialize,
 * or after the first time the user attempts to submit the form and hCaptcha finishes running.
 */
async function setupHCaptcha( $form, $hCaptchaField, win ) {
	const loadingIndicator = new ProgressIndicatorWidget(
		mw.msg( 'hcaptcha-loading-indicator-label' )
	);
	loadingIndicator.$element.addClass( 'ext-confirmEdit-hCaptchaLoadingIndicator' );
	loadingIndicator.$element.hide();

	const errorWidget = new ErrorWidget();

	$hCaptchaField.after( loadingIndicator.$element, errorWidget.$element );

	const hCaptchaLoaded = new Promise( ( resolve, reject ) => {
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
				wiki: mw.config.get( 'wgDBname' )
			} );
			mw.errorLogger.logError(
				new Error( 'Unable to load hCaptcha script in secure enclave mode' ),
				'error.confirmedit'
			);

			reject( 'generic-error' );
		};

		document.head.appendChild( script );

	} );

	// Map of hCaptcha error codes to error message keys.
	const errorMap = {
		'challenge-closed': 'hcaptcha-challenge-closed',
		'challenge-expired': 'hcaptcha-challenge-expired',
		'generic-error': 'hcaptcha-generic-error'
	};

	// Errors that can be recovered from by restarting the workflow.
	const recoverableErrors = [
		'challenge-closed',
		'challenge-expired'
	];

	const captchaIdPromise = hCaptchaLoaded.then( () => win.hcaptcha.render( 'h-captcha' ) );

	/**
	 * Trigger a single hCaptcha workflow execution.
	 *
	 * @return {Promise<void>} A promise that resolves if hCaptcha failed to initialize,
	 * or after the first time the user attempts to submit the form and hCaptcha finishes running.
	 */
	const executeWorkflow = async function () {
		$form.off( 'submit.hCaptcha' );

		const formSubmitted = new Promise( ( resolve ) => {
			$form.on( 'submit.hCaptcha', function ( event ) {
				event.preventDefault();

				resolve( this );
			} );
		} );

		try {
			const [ captchaId, form ] = await Promise.all( [ captchaIdPromise, formSubmitted ] );

			loadingIndicator.$element.show();
			performance.mark( 'hcaptcha-execute-start' );

			try {
				const { response } = await win.hcaptcha.execute( captchaId, { async: true } );

				// Clear out any errors from a previous workflow.
				errorWidget.hide();
				// Set the hCaptcha response input field, which does not yet exist
				$form.append( $( '<input>' )
					.attr( 'type', 'hidden' )
					.attr( 'name', 'h-captcha-response' )
					.attr( 'id', 'h-captcha-response' )
					.val( response ) );
				mw.track( 'stats.mediawiki_confirmedit_hcaptcha_form_submit_total', 1, {
					wiki: mw.config.get( 'wgDBname' )
				} );
				form.submit();
			} finally {
				trackPerformanceTiming(
					'hcaptcha-execute',
					'hcaptcha-execute-start',
					'hcaptcha-execute-complete'
				);
				loadingIndicator.$element.hide();
			}
		} catch ( error ) {
			const errMsg = Object.prototype.hasOwnProperty.call( errorMap, error ) ?
				errorMap[ error ] :
				'hcaptcha-generic-error';

			// Possible message keys used here:
			// * hcaptcha-generic-error
			// * hcaptcha-challenge-closed
			// * hcaptcha-challenge-expired
			errorWidget.show( mw.msg( errMsg ) );
			mw.errorLogger.logError( new Error( errMsg ), 'error.confirmedit' );
			mw.track(
				'stats.mediawiki_confirmedit_hcaptcha_execute_workflow_error_total', 1, {
					code: error.replace( /-/g, '_' ),
					wiki: mw.config.get( 'wgDBname' )
				}
			);

			// Initiate a new workflow for recoverable errors (e.g. an expired or closed challenge).
			if ( recoverableErrors.includes( error ) ) {
				return executeWorkflow();
			}
		}
	};

	return executeWorkflow();
}

/**
 * Configure hCaptcha in Secure Enclave mode.
 *
 * @param {Window} win
 * @return {Promise<void>} A promise that resolves if hCaptcha failed to initialize,
 * or after the first time the user attempts to submit the form and hCaptcha finishes running.
 */
async function useSecureEnclave( win ) {
	// eslint-disable-next-line no-jquery/no-global-selector
	const $hCaptchaField = $( '#h-captcha' );
	if ( !$hCaptchaField.length ) {
		return;
	}

	const $form = $hCaptchaField.closest( 'form' );
	if ( !$form.length ) {
		return;
	}

	// Load hCaptcha the first time the user interacts with the form.
	return new Promise( ( resolve ) => {
		const $inputs = $form.find( 'input' );

		// Catch and prevent form submissions that occur before hCaptcha was initialized.
		$form.one( 'submit.hCaptchaLoader', ( event ) => {
			event.preventDefault();

			$inputs.off( 'input.hCaptchaLoader focus.hCaptchaLoader' );
			$form.off( 'submit.hCaptchaLoader' );

			resolve( setupHCaptcha( $form, $hCaptchaField, win ) );
		} );

		$inputs.one( 'input.hCaptchaLoader focus.hCaptchaLoader', () => {
			$inputs.off( 'input.hCaptchaLoader focus.hCaptchaLoader' );
			$form.off( 'submit.hCaptchaLoader' );

			resolve( setupHCaptcha( $form, $hCaptchaField, win ) );
		} );
	} );
}

module.exports = useSecureEnclave;
