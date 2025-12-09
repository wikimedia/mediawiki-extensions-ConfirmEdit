const ProgressIndicatorWidget = require( './ProgressIndicatorWidget.js' );
const ErrorWidget = require( './ErrorWidget.js' );
const wiki = mw.config.get( 'wgDBname' );
const { loadHCaptcha, executeHCaptcha, mapErrorCodeToMessageKey } = require( './utils.js' );

/**
 * If set, makes the next call to isSaveRequest() to return true unconditionally.
 *
 * This gets set when the Javascript configuration variable
 * wgHCaptchaTriggerFormSubmission is set, so the user does not have to manually
 * resubmit the form after an AbuseFilter consequence.
 *
 * @type {boolean}
 */
let editFormForceIsSaveRequest = false;

/**
 * Holds a Promise that resolves once the call to render the captcha resolves,
 * or null if setupHCaptcha() has not been called yet.
 *
 * When this Promise resolves, hCaptcha is already set up and would intercept
 * form submissions. Therefore, at that point it is safe to trigger a form
 * submission programmatically.
 *
 * @type {?Promise<void>}
 */
let captchaIdPromise = null;

/**
 * Load hCaptcha in Secure Enclave mode.
 *
 * @param {jQuery} $form The form to be protected by hCaptcha.
 * @param {jQuery} $hCaptchaField The hCaptcha input field within the form.
 * @param {Window} win
 * @param {string} interfaceName The name of the interface where hCaptcha is being used
 * @return {Promise<void>} A promise that resolves if hCaptcha failed to initialize,
 * or after the first time the user attempts to submit the form and hCaptcha finishes running.
 */
async function setupHCaptcha( $form, $hCaptchaField, win, interfaceName ) {
	const loadingIndicator = new ProgressIndicatorWidget(
		mw.msg( 'hcaptcha-loading-indicator-label' )
	);
	loadingIndicator.$element.addClass( 'ext-confirmEdit-hCaptchaLoadingIndicator' );
	loadingIndicator.$element.hide();

	const errorWidget = new ErrorWidget();

	$hCaptchaField.after( loadingIndicator.$element, errorWidget.$element );

	const hCaptchaLoaded = loadHCaptcha( win, interfaceName );

	const setSubmitButtonDisabledProp = ( disabled ) => {
		if ( interfaceName === 'edit' ) {
			// On wikitext editor, use OOUI widget
			const $wpSaveWidget = $form.find( '#wpSaveWidget' );
			const saveButtonWidget = OO.ui.infuse( $wpSaveWidget );
			saveButtonWidget.setDisabled( disabled );
			return;
		}
		// For Special:CreateAccount use disabled attribute
		$form.find( 'input[type="submit"], button[type="submit"]' ).prop( 'disabled', disabled );
	};

	// Errors that can be recovered from by restarting the workflow.
	const recoverableErrors = [
		'challenge-closed',
		'challenge-expired',
		'internal-error',
		'network-error',
		'rate-limited'
	];

	/**
	 * Fires when a visible challenge is displayed.
	 */
	const onOpen = function () {
		mw.track( 'stats.mediawiki_confirmedit_hcaptcha_open_callback_total', 1, {
			wiki: wiki,
			interfaceName: interfaceName
		} );
		// Fire an event that can be used in WikimediaEvents for associating
		// challenge opens with a user.
		mw.track( 'confirmEdit.hCaptchaRenderCallback', 'open', interfaceName );
	};

	captchaIdPromise = hCaptchaLoaded.then( () => win.hcaptcha.render( 'h-captcha', {
		'open-callback': onOpen,
		'close-callback': () => {
			mw.track( 'confirmEdit.hCaptchaRenderCallback', 'close', interfaceName );
		},
		'chalexpired-callback': () => {
			mw.track( 'confirmEdit.hCaptchaRenderCallback', 'chalexpired', interfaceName );
		},
		'expired-callback': () => {
			mw.track( 'confirmEdit.hCaptchaRenderCallback', 'expired', interfaceName );
		},
		'error-callback': ( errCode ) => {
			mw.track( 'confirmEdit.hCaptchaRenderCallback', 'error', interfaceName, errCode );
		}
	} ) );

	/**
	 * Determines if the given form submission is a "save" request.
	 *
	 * For the form used for editing a page, the request is considered a
	 * "save" request if was indeed sent for saving the edit (as opposed to
	 * requesting a preview or a diff).
	 *
	 * Form submissions other than page edits are always considered "save"
	 * requests.
	 *
	 * This is used to determine whether a captcha challenge is required.
	 *
	 * @param {Object} event The jQuery form submission event
	 * @return {boolean}
	 */
	const isSaveRequest = ( event ) => {
		if ( editFormForceIsSaveRequest ) {
			editFormForceIsSaveRequest = false;

			return true;
		}

		let result = true;

		if ( $form.attr( 'id' ) === 'editform' ) {
			result = false;

			let originalEvent = event;

			if ( Object.hasOwnProperty.call( event, 'originalEvent' ) ) {
				originalEvent = event.originalEvent;
			}

			if ( typeof originalEvent.submitter === 'object' ) {
				result = ( originalEvent.submitter.id === 'wpSave' );
			}
		}

		return result;
	};

	/**
	 * Trigger a single hCaptcha workflow execution.
	 *
	 * @return {Promise<void>} A promise that resolves if hCaptcha failed to initialize,
	 * or after the first time the user attempts to submit the form and hCaptcha finishes running.
	 */
	const executeWorkflow = function () {
		$form.off( 'submit.hCaptcha' );

		const formSubmitted = new Promise( ( resolve ) => {
			$form.on( 'submit.hCaptcha', function ( event ) {
				if ( isSaveRequest( event ) ) {
					event.preventDefault();

					resolve( this );
				}
			} );
		} );

		/**
		 * Displays an error returned by attempting to load or execute hCaptcha
		 * in a user-friendly way
		 *
		 * @param {string} error The error as returned by `executeHCaptcha` or `loadHCaptcha`
		 */
		const displayErrorInErrorWidget = ( error ) => {
			// Possible message keys used here:
			// * hcaptcha-generic-error
			// * hcaptcha-challenge-closed
			// * hcaptcha-challenge-expired
			// * hcaptcha-internal-error
			// * hcaptcha-network-error
			// * hcaptcha-rate-limited
			errorWidget.show( mw.msg( mapErrorCodeToMessageKey( error ) ) );
		};

		return Promise.all( [ captchaIdPromise, formSubmitted ] )
			.then( ( [ captchaId, form ] ) => {
				loadingIndicator.$element.show();
				setSubmitButtonDisabledProp( true );

				return executeHCaptcha( win, captchaId, interfaceName )
					.then( ( response ) => {
						// Clear out any errors from a previous workflow.
						errorWidget.hide();
						// Set the hCaptcha response input field, which does not yet exist
						$form.append( $( '<input>' )
							.attr( 'type', 'hidden' )
							.attr( 'name', 'h-captcha-response' )
							.attr( 'id', 'h-captcha-response' )
							.val( response ) );

						// Hide the loading indicator as we have finished hCaptcha
						// and are submitting the form
						loadingIndicator.$element.hide();
						setSubmitButtonDisabledProp( false );

						mw.hook( 'confirmEdit.hCaptcha.executionSuccess' ).fire( response );

						form.submit();
					} )
					.catch( ( error ) => {
						loadingIndicator.$element.hide();
						setSubmitButtonDisabledProp( false );

						displayErrorInErrorWidget( error );

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

				// Note: If submissionHandler throws, the user won't be able
				// to submit the form anymore.
				setSubmitButtonDisabledProp( false );
				displayErrorInErrorWidget( error );
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

	// Work out what interface we are loading hCaptcha on
	let interfaceName = 'unknown';
	if ( mw.config.get( 'wgCanonicalSpecialPageName' ) === 'CreateAccount' ) {
		interfaceName = 'createaccount';
	}
	if ( mw.config.get( 'wgAction' ) === 'edit' || mw.config.get( 'wgAction' ) === 'submit' ) {
		interfaceName = 'edit';
	}

	// Load hCaptcha the first time the user interacts with the form, or load it
	// immediately if wgHCaptchaTriggerFormSubmission is set.
	return new Promise( ( resolve ) => {
		const $inputs = $form.find( 'input, textarea' );

		// Catch and prevent form submissions that occur before hCaptcha was initialized.
		$form.one( 'submit.hCaptchaLoader', ( event ) => {
			event.preventDefault();

			$inputs.off( 'input.hCaptchaLoader focus.hCaptchaLoader' );
			$form.off( 'submit.hCaptchaLoader' );

			resolve( setupHCaptcha( $form, $hCaptchaField, win, interfaceName ) );
		} );

		$inputs.one( 'input.hCaptchaLoader focus.hCaptchaLoader', () => {
			$inputs.off( 'input.hCaptchaLoader focus.hCaptchaLoader' );
			$form.off( 'submit.hCaptchaLoader' );

			resolve( setupHCaptcha( $form, $hCaptchaField, win, interfaceName ) );
		} );

		// If the backend requested to submit the form once the page is loaded,
		// trigger the setup immediately, wait a bit so it has a chance to load
		// the SDK, and then trigger the form submission programmatically.
		if ( mw.config.get( 'wgHCaptchaTriggerFormSubmission' ) ) {
			editFormForceIsSaveRequest = true;

			// Note setupHCaptcha() is not awaited here since it won't resolve
			// until the form is submitted, but the submission is triggered
			// in the next line once loading hCaptcha completes.
			setupHCaptcha( $form, $hCaptchaField, win, interfaceName );

			// Note that although captchaIdPromise is initialized by the async
			// function setupHCaptcha, that function does not await any promise
			// before it does so and, therefore, it is guaranteed that a Promise
			// is assigned to captchaIdPromise before we call .then() here.
			captchaIdPromise.then( () => $form.trigger( 'submit' ) );
		}
	} );
}

module.exports = useSecureEnclave;
