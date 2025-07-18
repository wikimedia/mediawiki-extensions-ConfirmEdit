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
		`mediawiki_special_createaccount_${ topic.replace( /-/g, '_' ) }_duration_seconds`,
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

		mw.loader.getScript( hCaptchaApiUrl.toString() )
			.catch( () => {
				trackPerformanceTiming(
					'hcaptcha-load',
					'hcaptcha-load-start',
					'hcaptcha-load-complete'
				);

				mw.errorLogger.logError(
					new Error( 'Unable to load hCaptcha script in secure enclave mode' ),
					'error.confirmedit'
				);

				reject();
			} );
	} );

	// Map of hCaptcha error codes to error message keys.
	const errorMap = {
		// Custom error code used to map getScript() failures into a user-visible error.
		'wmf-hcaptcha-load-error': 'hcaptcha-load-error',
		'rate-limited': 'hcaptcha-rate-limited',
		'network-error': 'hcaptcha-load-error',
		'challenge-closed': 'hcaptcha-challenge-closed',
		'challenge-expired': 'hcaptcha-challenge-expired'
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

		const result = captchaIdPromise
			.then(
				( captchaID ) => {
					loadingIndicator.$element.show();
					performance.mark( 'hcaptcha-execute-start' );
					return win.hcaptcha.execute( captchaID, { async: true } );
				},
				// Map getScript() failures into a user-visible error.
				// eslint-disable-next-line unicorn/no-useless-promise-resolve-reject
				() => Promise.reject( 'wmf-hcaptcha-load-error' )
			)
			.then(
				( { response } ) => {
					trackPerformanceTiming(
						'hcaptcha-execute',
						'hcaptcha-execute-start',
						'hcaptcha-execute-complete'
					);

					loadingIndicator.$element.hide();
					// Clear out any errors from a previous workflow.
					errorWidget.hide();
					// handle hCaptcha response token
					$form.find( '#h-captcha-response' ).val( response );
				},
				// Convert recoverable errors into a resolved value
				// so that we can delay showing them until the first submit attempt.
				( error ) => {
					trackPerformanceTiming(
						'hcaptcha-execute',
						'hcaptcha-execute-start',
						'hcaptcha-execute-complete'
					);

					loadingIndicator.$element.hide();
					return recoverableErrors.includes( error ) ? error : Promise.reject( error );
				}
			);

		const formSubmitted = new Promise( ( resolve ) => {
			$form.on( 'submit.hCaptcha', function ( event ) {
				event.preventDefault();

				resolve( this );
			} );
		} );

		try {
			const [ error, form ] = await Promise.all( [ result, formSubmitted ] );
			if ( !error ) {
				form.submit();
				return;
			}

			// Show an error message for recoverable errors and initiate a new workflow.
			// Possible message keys used here:
			// * hcaptcha-challenge-closed
			// * hcaptcha-challenge-expired
			errorWidget.show( mw.msg( errorMap[ error ] ) );
			return executeWorkflow();
		} catch ( error ) {
			// Handle unrecoverable errors.
			const errMsg = Object.prototype.hasOwnProperty.call( errorMap, error ) ?
				errorMap[ error ] :
				'hcaptcha-unknown-error';

			// Possible message keys used here:
			// * hcaptcha-load-error
			// * hcaptcha-rate-limited
			// * hcaptcha-unknown-error
			errorWidget.show( mw.msg( errMsg ) );
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
