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
	}

	if ( !this.config.interfaceName ) {
		this.config.interfaceName = 'unknown';
	}

	this.captchaWord = '';
	this.captchaId = '';

	this.captchaRendered = false;
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

	return Promise.reject( 'CAPTCHA not supported' );
};

/**
 * Returns a promise that resolves to an object containing the CAPTCHA data to be
 * submitted with the action. You should be able to append this to the API request.
 *
 * May execute code that gives a visual challenge or other step for the user to complete
 * depending on the implementation, and so should not be called until the user has clicked
 * the button to submit the action.
 *
 * @return {Promise<{captchaid: string, captchaword: string}>}
 */
mw.libs.confirmEdit.CaptchaWidget.prototype.getCaptchaDataForSubmission = function () {
	if ( !this.captchaRendered ) {
		return Promise.reject( 'Render the CAPTCHA before getting the CAPTCHA data' );
	}

	return Promise.reject( 'CAPTCHA not supported' );
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
	if ( captchaData.type && captchaData.type.toLowerCase() !== this.config.type ) {
		this.config.type = captchaData.type.toLowerCase();
		if ( this.captchaRendered ) {
			this.captchaRendered = false;
			return this.renderCaptcha();
		}
	}
	return Promise.resolve();
};
