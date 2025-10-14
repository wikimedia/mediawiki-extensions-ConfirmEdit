/**
 * Defines and installs the hCaptcha plugin for VisualEditor that displays hCaptcha
 * when a user needs to complete hCaptcha for a "generic" edit.
 * A generic edit is an edit where a user needing to fill out a captcha is not
 * dependent on the content of the edit.
 *
 * Returns a callback that should be executed in initPlugins.js after `ve.init.mw.HCaptcha`
 * is loaded
 */
module.exports = () => {
	ve.init.mw.HCaptchaOnLoadHandler = function () {};

	OO.inheritClass( ve.init.mw.HCaptchaOnLoadHandler, ve.init.mw.HCaptcha );

	/**
	 * Load the hCaptcha SDK when a user changes content in the VisualEditor editor if
	 * hCaptcha is required for a "generic" edit.
	 *
	 * @return {void}
	 */
	ve.init.mw.HCaptchaOnLoadHandler.static.onActivationComplete = function () {
		if ( !this.shouldRun() ) {
			return;
		}

		const surface = ve.init.target.surface;
		surface.getModel().getDocument().once( 'transact', () => {
			this.getReadyPromise();
		} );
	};

	/**
	 * Returns whether this code should do anything. If it returns false,
	 * then the code is essentially a no-op.
	 *
	 * Intended to ensure that hCaptcha is only loaded when needed and when the
	 * definitely needs to solve a captcha to make the edit.
	 *
	 * @return {boolean}
	 */
	ve.init.mw.HCaptchaOnLoadHandler.static.shouldRun = function () {
		return mw.config.get( 'wgConfirmEditCaptchaNeededForGenericEdit' ) === 'hcaptcha';
	};

	/**
	 * Initialises the hCaptcha VisualEditor on load handler for the current page.
	 */
	ve.init.mw.HCaptchaOnLoadHandler.static.init = function () {
		mw.hook( 've.activationComplete' ).add( () => ve.init.mw.HCaptchaOnLoadHandler.static.onActivationComplete() );
	};
};
