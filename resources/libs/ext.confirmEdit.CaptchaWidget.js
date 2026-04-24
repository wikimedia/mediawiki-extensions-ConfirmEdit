mw.libs.confirmEdit = mw.libs.confirmEdit || {};

/**
 * @typedef CaptchaWidgetConfig
 * @property {Element|string} container The element to render the CAPTCHA in, identified by either
 *   a selector that identifies one element or an {@link Element} object. The container must exist
 *   in the DOM before calling {@link mw.libs.confirmEdit.CaptchaWidget.prototype.renderCaptcha}.
 * @property {string} [type] The type of CAPTCHA to render. Can be set later via
 *   {@link self.updateForCaptchaFailure} if not known at the time of construction.
 * @property {string} [interfaceName] The name of the interface where the action is being performed,
 *   used for stats.
 */

/**
 * @typedef CaptchaSubmissionData
 * @property {string} captchaid
 * @property {string} captchaword
 * @property {boolean} [wgConfirmEditForceShowCaptcha]
 */

/**
 * @class
 *
 * Creates a class which can be used to render a CAPTCHA and get the captcha data for use
 * in an API request.
 *
 * @constructor
 * @param {CaptchaWidgetConfig} [config] Configuration options for the CAPTCHA widget
 */
mw.libs.confirmEdit.CaptchaWidget = function MwCaptchaWidget( config ) {
	this.config = config || {};

	if ( !this.config.container ) {
		throw new Error( 'CaptchaWidget requires a container to render the CAPTCHA in' );
	}

	if ( this.config.type ) {
		this.config.type = this.config.type.toLowerCase();

		if ( this.config.type === 'image' ) {
			this.config.type = 'fancycaptcha';
		}
	}

	if ( !this.config.interfaceName ) {
		this.config.interfaceName = 'unknown';
	}

	this.captchaWord = '';
	this.captchaId = '';

	this.captchaRendered = false;

	// hCaptcha config (unused if not using hCaptcha)
	this.hCaptchaSiteKey = '';
	this.hCaptchaWidgetId = '';
	this.hCaptchaForceShowCaptcha = false;

	// Question based CAPTCHA config (unused if not question based)
	this.captchaQuestion = '';
	this.captchaQuestionMime = '';

	// FancyCaptcha config (unused if not using FancyCaptcha)
	this.captchaImageUrl = '';

	this.captchaInputField = null;
};

mw.libs.confirmEdit.CaptchaWidget.static = {};

/**
 * Returns the type of CAPTCHA that would be required to complete any edit, or false
 * if no CAPTCHA is required.
 *
 * A CAPTCHA may still be required to perform the edit based on the edit content, but this
 * can only be known after the first attempt to make the edit has occurred. If this returns
 * a string, the caller if possible should show a CAPTCHA to the user before submitting the
 * API request.
 *
 * @return {false|string}
 */
mw.libs.confirmEdit.CaptchaWidget.static.captchaNeededForEdit = function () {
	return mw.config.get( 'wgConfirmEditCaptchaNeededForGenericEdit' );
};

/**
 * Renders the CAPTCHA interface for use in a form.
 *
 * Calling this method will clear any existing CAPTCHA in the container provided
 * in the configuration. The type of the CAPTCHA must have been specified before calling.
 *
 * @return {Promise} A promise that completes when the CAPTCHA has been rendered,
 *   or rejects if the rendering failed
 */
mw.libs.confirmEdit.CaptchaWidget.prototype.renderCaptcha = function () {
	if ( !this.config.type ) {
		return Promise.reject( 'CAPTCHA type not specified' );
	}

	const $container = $( this.config.container );
	if ( !$container.closest( document.documentElement ).length ) {
		return Promise.reject(
			'CAPTCHA container should exist in the DOM before calling renderCaptcha'
		);
	}

	const $captchaContainer = $( '<div>' );
	$captchaContainer.addClass( 'mw-confirmEdit-captchaWidget' );

	$container.find( '.mw-confirmEdit-captchaWidget' ).remove();
	$container.append( $captchaContainer );

	switch ( this.config.type ) {
		case 'hcaptcha':
			return this.renderHCaptcha( $captchaContainer );
		case 'simple':
		case 'question':
			return this.renderQuestionCaptcha( $captchaContainer );
		case 'fancycaptcha':
			return this.renderFancyCaptcha( $captchaContainer );
	}

	return Promise.reject( 'CAPTCHA not supported' );
};

/**
 * Renders the hCaptcha widget using the `ext.confirmEdit.hCaptcha` module
 * Only for use by {@link self.renderCaptcha}.
 *
 * @internal
 * @param {jQuery} $captchaContainer
 * @return {Promise}
 */
mw.libs.confirmEdit.CaptchaWidget.prototype.renderHCaptcha = function ( $captchaContainer ) {
	// A new hCaptcha widget will generate a new hCaptcha token, so rerendering needs to clear this out
	this.captchaWord = '';

	return mw.loader.using( 'ext.confirmEdit.hCaptcha' ).then( ( require ) => {
		const hCaptchaUtils = require( 'ext.confirmEdit.hCaptcha' ).utils;
		return hCaptchaUtils.loadHCaptcha(
			window,
			this.config.interfaceName,
			{ render: 'explicit' }
		).then( () => {
			if ( hCaptchaUtils.isHCaptchaInInvisibleMode() ) {
				$captchaContainer.attr( 'data-size', 'invisible' );

				const $privacyPolicyNotice = $( '<div>' );
				$privacyPolicyNotice.html( mw.message( 'hcaptcha-privacy-policy' ).parse() );
				$privacyPolicyNotice.addClass( 'ext-confirmEdit-hcaptcha-privacy-policy' );
				$captchaContainer.append( $privacyPolicyNotice );
			}

			this.hCaptchaSiteKey = this.hCaptchaSiteKey || hCaptchaUtils.getHCaptchaSiteKey();
			this.hCaptchaWidgetId = hCaptchaUtils.renderHCaptcha(
				window,
				this.config.interfaceName,
				$captchaContainer[ 0 ],
				{
					sitekey: this.hCaptchaSiteKey,
					callback: ( token ) => {
						this.captchaWord = token;
					},
					'expired-callback': () => {
						this.captchaWord = '';
					}
				}
			);
			this.captchaRendered = true;
		} );
	} );
};

/**
 * if the type of CAPTCHA uses an input field for an answer, this method returns a reference
 * to the {@link Element} for the Codex input field.
 *
 * @return {Element|null}
 */
mw.libs.confirmEdit.CaptchaWidget.prototype.getInputField = function () {
	return this.captchaInputField;
};

/**
 * Renders a CAPTCHA that is based around a question (currently 'simple' or 'question').
 * Only for use by {@link self.renderCaptcha}.
 *
 * @internal
 * @param {jQuery} $captchaContainer
 * @return {Promise}
 */
mw.libs.confirmEdit.CaptchaWidget.prototype.renderQuestionCaptcha = function ( $captchaContainer ) {
	if ( !this.captchaId || !this.captchaQuestionMime || !this.captchaQuestion ) {
		return Promise.reject( 'Please provide the captcha ID and question via updateForCaptchaFailure' );
	}

	const $captchaParagraph = $( '<div>' ).append(
		$( '<strong>' ).text( mw.msg( 'captcha-label' ) ),
		document.createTextNode( mw.msg( 'colon-separator' ) )
	);

	let question;
	switch ( this.captchaQuestionMime ) {
		case 'text/html':
			question = $.parseHTML( this.captchaQuestion );
			break;
		case 'text/plain':
			question = document.createTextNode( this.captchaQuestion );
			break;
		default:
			return Promise.reject( 'The mime type of the question is not recognised' );
	}

	const captchaLabel = this.config.type === 'question' ? 'questycaptcha-edit' : 'captcha-edit';

	// Possible messages in use here documented above
	// eslint-disable-next-line mediawiki/msg-doc
	$captchaParagraph.append( mw.message( captchaLabel ).parseDom(), '<br>', question );

	$captchaContainer.append( $captchaParagraph );
	$captchaContainer.append( this.createInputField() );

	this.captchaRendered = true;
	return Promise.resolve();
};

/**
 * Creates an input field for the user to type an answer into. Only used by CAPTCHAs
 * that need an input field for a typed out answer.
 *
 * @internal
 * @param {string} [placeholder] If defined, the text to be used as the placeholder for the input
 * @return {jQuery}
 */
mw.libs.confirmEdit.CaptchaWidget.prototype.createInputField = function ( placeholder ) {
	const $inputField = $( '<input>' )
		.addClass( 'cdx-text-input__input mw-confirmEdit-captchaInputField' )
		.attr( 'type', 'text' );
	if ( placeholder ) {
		$inputField.attr( 'placeholder', placeholder );
	}

	const $inputContainer = $( '<div>' )
		.addClass( 'cdx-text-input' )
		.append( $inputField );

	this.captchaInputField = $inputContainer[ 0 ];

	return $inputContainer;
};

/**
 * Renders a FancyCaptcha CAPTCHA. Only for use by {@link self.renderCaptcha}.
 *
 * @internal
 * @param {jQuery} $captchaContainer
 * @return {Promise}
 */
mw.libs.confirmEdit.CaptchaWidget.prototype.renderFancyCaptcha = function ( $captchaContainer ) {
	if ( !this.captchaId || !this.captchaImageUrl ) {
		return Promise.reject( 'Please provide the captcha ID and image URL via updateForCaptchaFailure' );
	}

	return mw.loader.using( 'ext.confirmEdit.fancyCaptcha' ).then( () => {
		const $captchaParagraph = $( '<div>' ).append(
			$( '<strong>' ).text( mw.msg( 'captcha-label' ) ),
			document.createTextNode( mw.msg( 'colon-separator' ) ),
			mw.message( 'fancycaptcha-edit' ).parseDom()
		);

		const $captchaInputField = this.createInputField( mw.msg( 'fancycaptcha-imgcaptcha-ph' ) );

		const $captchaImage = $( '<img>' )
			.attr( 'src', this.captchaImageUrl )
			.data( 'captchaId', this.captchaId )
			.addClass( 'fancycaptcha-image' )
			.on( 'fancycaptcha-reloaded', () => {
				this.captchaId = $captchaImage.data( 'captchaId' );
				$( 'input', $captchaInputField ).val( '' ).trigger( 'focus' );
			} );

		// jQuery docs say that the image "load" event is not reliably fired, so race against
		// a 1 second timeout to avoid an indefinitely unresolved promise.
		const imageLoadedPromise = Promise.race( [
			new Promise( ( resolve, reject ) => {
				$captchaImage
					.on( 'load', resolve )
					.on( 'error', () => reject( 'FancyCaptcha image failed to load' ) );
			} ),
			new Promise( ( resolve ) => {
				setTimeout( resolve, 1000 );
			} )
		] );

		const $captchaImageReloadLink = $( '<a>' )
			.addClass( 'fancycaptcha-reload' )
			.text( mw.msg( 'fancycaptcha-reload-text' ) );

		$captchaContainer.addClass( 'fancycaptcha-captcha-container' );
		$captchaContainer.append(
			$captchaParagraph,
			$captchaImage,
			' ',
			$captchaImageReloadLink,
			$captchaInputField
		);

		return imageLoadedPromise.then( () => {
			this.captchaRendered = true;
		} );
	} );
};

/**
 * Returns a promise that resolves to an object containing the CAPTCHA data to be
 * submitted with the action. You should be able to append this to the API request.
 *
 * May execute code that gives a visual challenge or other step for the user to complete
 * depending on the implementation, and so should not be called until the user has clicked
 * the button to submit the action.
 *
 * @return {Promise<CaptchaSubmissionData>}
 */
mw.libs.confirmEdit.CaptchaWidget.prototype.getCaptchaDataForSubmission = function () {
	if ( !this.captchaRendered ) {
		return Promise.reject( 'Render the CAPTCHA before getting the CAPTCHA data' );
	}

	const captchaDataResolver = () => {
		const captchaData = {
			captchaid: this.captchaId,
			captchaword: this.captchaWord
		};
		if ( this.config.type === 'hcaptcha' && this.hCaptchaForceShowCaptcha === true ) {
			captchaData.wgConfirmEditForceShowCaptcha = true;
		}
		return captchaData;
	};

	switch ( this.config.type ) {
		case 'hcaptcha':
			return this.executeHCaptcha().then( captchaDataResolver );
		case 'simple':
		case 'question':
		case 'fancycaptcha':
			this.captchaWord = $( '.mw-confirmEdit-captchaInputField', this.config.container ).val();
			return Promise.resolve( captchaDataResolver() );
	}

	return Promise.reject( 'CAPTCHA not supported' );
};

/**
 * Executes hCaptcha and resolves or rejects the provided callbacks based on the result.
 * Only for use by {@link self.getCaptchaDataForSubmission}.
 *
 * @internal
 * @return {Promise<void>}
 */
mw.libs.confirmEdit.CaptchaWidget.prototype.executeHCaptcha = function () {
	if ( this.captchaWord ) {
		return Promise.resolve();
	} else {
		return mw.loader.using( 'ext.confirmEdit.hCaptcha' )
			.then( ( require ) => {
				const hCaptchaUtils = require( 'ext.confirmEdit.hCaptcha' ).utils;
				return hCaptchaUtils.executeHCaptcha(
					window,
					this.hCaptchaWidgetId,
					this.config.interfaceName
				).then( ( response ) => {
					this.captchaWord = response;
				} );
			} );
	}
};

/**
 * Updates the CAPTCHA based on the provided captcha API error response.
 *
 * You can call this method before or after calling {@link self.renderCaptcha}. If the CAPTCHA is
 * already rendered, doing this may re-render the CAPTCHA if the API response asks for
 * a different type of CAPTCHA or if other settings are different.
 *
 * @param {Object} captchaData The object returned as the captcha error in an API response
 * @return {Promise} A promise that resolves when the CAPTCHA widget has been updated
 */
mw.libs.confirmEdit.CaptchaWidget.prototype.updateForCaptchaFailure = function ( captchaData ) {
	let needsRerender = false;

	let captchaTypeFromData = captchaData.type;
	if ( captchaTypeFromData ) {
		captchaTypeFromData = captchaTypeFromData.toLowerCase();
		if ( captchaTypeFromData === 'image' ) {
			captchaTypeFromData = 'fancycaptcha';
		}

		if ( captchaTypeFromData !== this.config.type ) {
			this.config.type = captchaTypeFromData;
			needsRerender = true;
		}
	}

	if ( this.config.type === 'hcaptcha' ) {
		this.captchaWord = '';
		needsRerender = true;

		this.hCaptchaForceShowCaptcha = captchaData.error === 'forceshowcaptcha';
		if ( captchaData.key && this.hCaptchaSiteKey !== captchaData.key ) {
			this.hCaptchaSiteKey = captchaData.key;
		}
	}

	if ( this.config.type === 'simple' || this.config.type === 'question' ) {
		this.captchaQuestionMime = captchaData.mime;
		this.captchaQuestion = captchaData.question;
		this.captchaId = captchaData.id;
		needsRerender = true;
	}

	if ( this.config.type === 'fancycaptcha' ) {
		this.captchaImageUrl = captchaData.url;
		this.captchaId = captchaData.id;
		needsRerender = true;
	}

	if ( needsRerender && this.captchaRendered ) {
		this.captchaInputField = null;
		this.captchaRendered = false;
		return this.renderCaptcha();
	}

	return Promise.resolve();
};
