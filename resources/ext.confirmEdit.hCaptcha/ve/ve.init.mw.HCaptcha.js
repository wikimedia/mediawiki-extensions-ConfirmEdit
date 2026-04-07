/**
 * Base class for the hCaptcha VisualEditor handlers
 *
 * Returns a callback that should be executed in initPlugins.js
 */
module.exports = () => {
	// Load these here so that in QUnit tests we have a chance to mock utils.js
	const { loadHCaptcha } = require( './../utils.js' );
	const config = require( './../config.json' );

	ve.init.mw.HCaptcha = function () {};

	OO.initClass( ve.init.mw.HCaptcha );

	/**
	 * The return value of `hcaptcha.render`, which is the widget ID of the
	 * rendered hCaptcha widget. This can be used by `executeHCaptcha`
	 * to programmatically execute hCaptcha in invisible mode.
	 *
	 * @type {string|null} `null` if no hCaptcha widget is rendered yet
	 */
	ve.init.mw.HCaptcha.static.widgetId = null;

	ve.init.mw.HCaptcha.static.getReadyPromise = function () {
		if ( !this.readyPromise ) {
			this.readyPromise = loadHCaptcha( window, 'visualeditor', { render: 'explicit' } );
		}

		return this.readyPromise;
	};

	/**
	 * Renders the hCaptcha privacy policy notice if invisible mode is enabled.
	 * Should ideally be called as soon as it is known that hCaptcha needs to be shown.
	 *
	 * @param {jQuery} $hCaptchaContainer The element to add the privacy policy notice to
	 */
	ve.init.mw.HCaptcha.static.renderHCaptchaPrivacyPolicyNotice = function ( $hCaptchaContainer ) {
		if ( config.HCaptchaInvisibleMode ) {
			const $privacyPolicyNotice = $( '<div>' );
			$privacyPolicyNotice.html( mw.message( 'hcaptcha-privacy-policy' ).parse() );
			$privacyPolicyNotice.addClass( 'ext-confirmEdit-hcaptcha-privacy-policy ve-ui-mwSaveDialog-license' );
			$hCaptchaContainer.append( $privacyPolicyNotice );
		}
	};

	/**
	 * Renders the hCaptcha widget in the VisualEditor save dialog.
	 *
	 * @param {window} win
	 * @param {ve.init.Target} target
	 * @param {jQuery} $hCaptchaWidgetContainer
	 * @return {Promise} A promise that resolves when the hCaptcha widget has finished rendering
	 */
	ve.init.mw.HCaptcha.static.renderHCaptchaWidget = function (
		win,
		target,
		$hCaptchaWidgetContainer
	) {
		let renderPromiseResolver = null;
		const renderPromise = new Promise( ( resolve ) => {
			renderPromiseResolver = resolve;
		} );

		const saveDialog = target.saveDialog;
		const siteKey = mw.config.get( 'wgConfirmEditHCaptchaSiteKey' ) || config.HCaptchaSiteKey;

		this.widgetId = win.hcaptcha.render( $hCaptchaWidgetContainer[ 0 ], {
			sitekey: siteKey,
			callback: renderPromiseResolver
		} );

		saveDialog.updateSize();

		return renderPromise;
	};
};
