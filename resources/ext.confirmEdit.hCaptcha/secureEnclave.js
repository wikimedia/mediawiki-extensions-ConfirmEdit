const ProgressIndicatorWidget = require( './ProgressIndicatorWidget.js' );
const ErrorWidget = require( './ErrorWidget.js' );
const wiki = mw.config.get( 'wgDBname' );
const { loadHCaptcha, executeHCaptcha } = require( './utils.js' );

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

	const hCaptchaLoaded = loadHCaptcha( win );

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

	/**
	 * Fires when a visible challenge is displayed.
	 */
	const onOpen = function () {
		mw.track( 'stats.mediawiki_confirmedit_hcaptcha_open_callback_total', 1, {
			wiki: wiki
		} );
	};

	const captchaIdPromise = hCaptchaLoaded.then( () => win.hcaptcha.render( 'h-captcha', {
		'open-callback': onOpen
	} ) );

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

		const [ captchaId, form ] = await Promise.all( [ captchaIdPromise, formSubmitted ] );

		loadingIndicator.$element.show();

		await executeHCaptcha( win, captchaId )
			.then( ( response ) => {
				// Clear out any errors from a previous workflow.
				errorWidget.hide();
				// Set the hCaptcha response input field, which does not yet exist
				$form.append( $( '<input>' )
					.attr( 'type', 'hidden' )
					.attr( 'name', 'h-captcha-response' )
					.attr( 'id', 'h-captcha-response' )
					.val( response ) );
				form.submit();
			} )
			.catch( ( error ) => {
				loadingIndicator.$element.hide();

				const errMsg = Object.prototype.hasOwnProperty.call( errorMap, error ) ?
					errorMap[ error ] :
					'hcaptcha-generic-error';

				// Possible message keys used here:
				// * hcaptcha-generic-error
				// * hcaptcha-challenge-closed
				// * hcaptcha-challenge-expired
				errorWidget.show( mw.msg( errMsg ) );

				// Initiate a new workflow for recoverable errors
				// (e.g. an expired or closed challenge).
				if ( recoverableErrors.includes( error ) ) {
					return executeWorkflow();
				}
			} );
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
