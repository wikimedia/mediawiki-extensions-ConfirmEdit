/**
 * Handler for CAPTCHA save failures for FancyCaptcha, QuestyCaptcha, and SimpleCaptcha.
 * Handling for other CAPTCHA types are in other modules.
 *
 * Captcha "errors" are not usually errors. We simply just don't always know about them ahead
 * of time. So once save happens then the API returns with a CAPTCHA if it's required and
 * the user can try again after they solve the CAPTCHA.
 */
module.exports = () => {
	ve.init.mw.CaptchaSaveErrorHandler = function () {};

	OO.inheritClass( ve.init.mw.CaptchaSaveErrorHandler, ve.init.mw.SaveErrorHandler );

	ve.init.mw.CaptchaSaveErrorHandler.static.name = 'confirmEditCaptchas';

	ve.init.mw.CaptchaSaveErrorHandler.static.matchFunction = function ( data ) {
		const captchaData = ve.getProp( data, 'visualeditoredit', 'edit', 'captcha' );

		return !!( captchaData && (
			captchaData.url ||
			captchaData.type === 'simple' ||
			captchaData.type === 'question'
		) );
	};

	ve.init.mw.CaptchaSaveErrorHandler.static.process = function ( data, target ) {
		const captchaInput = new mw.libs.confirmEdit.CaptchaInputWidget(
			ve.getProp( data, 'visualeditoredit', 'edit', 'captcha' )
		);
		ve.targetLinksToNewWindow( captchaInput.$element[ 0 ] );

		function onCaptchaLoad() {
			target.saveDialog.updateSize();
			captchaInput.focus();
			captchaInput.scrollElementIntoView();
		}

		captchaInput.on( 'load', onCaptchaLoad );
		// Save when pressing 'Enter' in captcha field as it is single line.
		captchaInput.on( 'enter', () => {
			target.saveDialog.executeAction( 'save' );
		} );

		// Register extra fields
		target.saveFields.wpCaptchaId = function () {
			return captchaInput.getCaptchaId();
		};
		target.saveFields.wpCaptchaWord = function () {
			return captchaInput.getCaptchaWord();
		};

		// ProcessDialog's error system isn't great for this yet.
		target.saveDialog.clearMessage( 'api-save-error' );
		target.saveDialog.showMessage( 'api-save-error', captchaInput.$element, { wrap: false } );
		target.saveDialog.popPending();
		onCaptchaLoad();

		// Emit event for tracking. TODO: This is a bad design
		target.emit( 'saveErrorCaptcha' );
	};

	ve.init.mw.saveErrorHandlerFactory.register( ve.init.mw.CaptchaSaveErrorHandler );
};
