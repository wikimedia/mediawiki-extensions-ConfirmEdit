const utils = require( '../utils.js' );

/**
 * Holds a Promise that resolves to the captcha ID once render() has completed,
 * or null if render() has not been called yet.
 *
 * @type {?Promise<string>}
 */
let captchaIdPromise = null;

/**
 * The DOM element that captchaIdPromise was rendered into, or null if render()
 * has not been called yet.
 *
 * Stored alongside captchaIdPromise so that if the container is replaced (e.g.
 * by cleanupDuplicateHCaptchaContainers() in the AbuseFilter flow), render()
 * is called again into the new element rather than executing against a detached
 * widget.
 *
 * @type {?HTMLElement}
 */
let captchaContainerElement = null;

/**
 * Load hCaptcha in Secure Enclave mode.
 *
 * The Promise returned by this method resolves after the first time the user
 * attempts to submit the form and hCaptcha finishes running.
 *
 * If either the hCaptcha SDK fails to initialize or there is an error rendering
 * the captcha, the Promise will also resolve but the function will first show
 * an error in an error widget and re-enable the Save button to let the user try
 * again.
 *
 * @param {jQuery} $hCaptchaField The hCaptcha input field within the form.
 * @param {Window} win Reference to the browser window.
 * @param {string} interfaceName The name of the interface where hCaptcha is being used.
 *
 * @return {Promise<void>} Resolves when a workflow finishes (successfully or not).
 */
async function setupHCaptcha( $hCaptchaField, win, interfaceName ) {
	/**
	 * Disables or re-enables both the submit button and the back button located
	 * in the top bar of the MobileFrontend source editor.
	 *
	 * @param {boolean} disabled
	 */
	const setSubmitButtonDisabledProp = ( disabled ) => {
		// .header-action refers to the topmost header added by the
		// MobileFrontend to hold the action buttons for the editor.
		// It is rendered by mobile.startup/headers.js.
		const $saveButton = $( '.header-action button.save', win.document );
		$saveButton.prop( 'disabled', disabled );

		// Enable/disable also the Back button.
		const $backButton = $( '.header-cancel button.back', win.document );
		$backButton.prop( 'disabled', disabled );
	};

	// Errors that can be recovered from by restarting the workflow.
	const recoverableErrors = utils.getRecoverableErrors( interfaceName );

	const hCaptchaDomElement = $hCaptchaField[ 0 ];
	if ( captchaIdPromise === null || captchaContainerElement !== hCaptchaDomElement ) {
		captchaIdPromise = utils.loadAndRenderHCaptcha(
			win,
			interfaceName,
			hCaptchaDomElement
		);
		captchaContainerElement = hCaptchaDomElement;
	}

	/**
	 * Trigger a single hCaptcha workflow execution.
	 *
	 * @return {Promise<void>} A promise that resolves if hCaptcha failed to
	 *                         initialize, or after hCaptcha finishes running.
	 */
	const executeWorkflow = function () {
		setSubmitButtonDisabledProp( true );

		return captchaIdPromise.then( ( captchaId ) => {
			utils.hideError( $hCaptchaField );
			utils.showLoadingIndicator( $hCaptchaField );

			return utils.executeHCaptcha( win, captchaId, interfaceName )
				.then( ( response ) => {
					// Clear out any errors from a previous workflow.
					utils.hideError( $hCaptchaField );
					utils.hideLoadingIndicator( $hCaptchaField );
					setSubmitButtonDisabledProp( false );

					// Let extensions know that the captcha was successfully verified.
					// Specifically, this makes the MobileFrontend submit the current edit.
					mw.hook( 'confirmEdit.hCaptcha.executionSuccess' ).fire( response );
				} )
				.catch( ( error ) => {
					utils.showError( $hCaptchaField, error );
					utils.hideLoadingIndicator( $hCaptchaField );
					setSubmitButtonDisabledProp( false );

					// Initiate a new workflow for recoverable errors
					// (e.g. an expired or closed challenge).
					if ( recoverableErrors.includes( error ) ) {
						return executeWorkflow();
					}
				} );
		} )
			.catch( ( error ) => {
				mw.track(
					'confirmEdit.hCaptchaRenderCallback',
					'error',
					interfaceName,
					// "error" is an hCaptcha error code (for example,
					// "rate-limited"). The full list of values can be found at
					// https://docs.hcaptcha.com/configuration/#error-codes
					error
				);

				utils.showError( $hCaptchaField, error );
				utils.hideLoadingIndicator( $hCaptchaField );
				setSubmitButtonDisabledProp( false );
			} );
	};

	return executeWorkflow();
}

/**
 * Configure hCaptcha in Secure Enclave mode for the MobileFrontend.
 *
 * This method requires callers to provide an interface name (such as
 * "mobilefrontend-editor"), which is used for instrumentation purposes.
 *
 * @param {Window} win Reference to the browser window.
 * @param {string} interfaceName interface hCaptcha is being loaded on.
 * @return {Promise<void>} A promise that resolves if hCaptcha failed to initialize,
 * or after the first time the user attempts to submit the form and hCaptcha finishes running.
 */
function mobileFrontendSecureEnclave( win, interfaceName ) {
	// eslint-disable-next-line no-jquery/no-global-selector
	const $hCaptchaField = $( '#h-captcha' );
	if ( !$hCaptchaField.length ) {
		return Promise.resolve();
	}

	// Editing in the MobileFrontend does not trigger a form submission, so
	// the setup needs to be called explicitly.
	return setupHCaptcha( $hCaptchaField, win, interfaceName );
}

module.exports = mobileFrontendSecureEnclave;
