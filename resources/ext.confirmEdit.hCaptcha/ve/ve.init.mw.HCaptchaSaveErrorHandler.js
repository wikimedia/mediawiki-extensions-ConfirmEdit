/**
 * Defines and installs the hCaptcha plugin for VisualEditor. This should be called
 * only when VisualEditor is loaded and ideally through a callback provided to
 * `mw.libs.ve.targetLoader.addPlugin`
 */
module.exports = () => {
	// Load these here so that in QUnit tests we have a chance to mock utils.js
	const config = require( './../config.json' );
	const { loadHCaptcha } = require( './../utils.js' );

	ve.init.mw.HCaptchaSaveErrorHandler = function () {};

	OO.inheritClass( ve.init.mw.HCaptchaSaveErrorHandler, ve.init.mw.SaveErrorHandler );

	ve.init.mw.HCaptchaSaveErrorHandler.static.name = 'confirmEditHCaptcha';

	ve.init.mw.HCaptchaSaveErrorHandler.static.getReadyPromise = function () {
		if ( !this.readyPromise ) {
			this.readyPromise = loadHCaptcha( window, 'visualeditor', { render: 'explicit' } );
		}

		return this.readyPromise;
	};

	ve.init.mw.HCaptchaSaveErrorHandler.static.matchFunction = function ( data ) {
		const captchaData = ve.getProp( data, 'visualeditoredit', 'edit', 'captcha' );

		return !!( captchaData && captchaData.type === 'hcaptcha' );
	};

	ve.init.mw.HCaptchaSaveErrorHandler.static.process = function ( data, target ) {
		const self = this,
			siteKey = config.HCaptchaSiteKey,
			$container = $( '<div>' );

		// Register extra fields
		target.saveFields.wpCaptchaWord = function () {
			// eslint-disable-next-line no-jquery/no-global-selector
			return $( '[name=h-captcha-response]' ).val();
		};

		this.getReadyPromise()
			.then( () => {
				// ProcessDialog's error system isn't great for this yet.
				target.saveDialog.clearMessage( 'api-save-error' );
				target.saveDialog.showMessage( 'api-save-error', $container, { wrap: false } );
				self.widgetId = window.hcaptcha.render( $container[ 0 ], {
					sitekey: siteKey,
					callback: function () {
						target.saveDialog.executeAction( 'save' );
					},
					'expired-callback': function () {},
					'error-callback': function () {}
				} );
				target.saveDialog.popPending();
				target.saveDialog.updateSize();

				target.emit( 'saveErrorCaptcha' );
			} );
	};

	ve.init.mw.saveErrorHandlerFactory.register( ve.init.mw.HCaptchaSaveErrorHandler );
};
