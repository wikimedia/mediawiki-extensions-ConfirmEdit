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

	/**
	 * The CAPTCHA that is currently shown to the user, or null if no CAPTCHA is shown
	 *
	 * @type {mw.libs.confirmEdit.CaptchaWidget|null}
	 */
	ve.init.mw.CaptchaSaveErrorHandler.static.captchaWidget = null;

	ve.init.mw.CaptchaSaveErrorHandler.static.matchFunction = function ( data ) {
		const captchaData = ve.getProp( data, 'visualeditoredit', 'edit', 'captcha' );

		return !!( captchaData && (
			captchaData.url ||
			captchaData.type === 'simple' ||
			captchaData.type === 'question'
		) );
	};

	ve.init.mw.CaptchaSaveErrorHandler.static.process = function ( data, target ) {
		const $captchaContainer = $( '<div>' );
		target.saveDialog.clearMessage( 'api-save-error' );
		target.saveDialog.showMessage( 'api-save-error', $captchaContainer, { wrap: false } );

		this.captchaWidget = new mw.libs.confirmEdit.CaptchaWidget( {
			container: $captchaContainer[ 0 ], interfaceName: 'visualeditor'
		} );
		this.captchaWidget.updateForCaptchaFailure( ve.getProp( data, 'visualeditoredit', 'edit', 'captcha' ) ).then(
			() => this.captchaWidget.renderCaptcha().then( () => {
				ve.targetLinksToNewWindow( $captchaContainer[ 0 ] );
				target.saveDialog.updateSize();

				const captchaInputField = this.captchaWidget.getInputField();

				OO.ui.Element.static.scrollIntoView( captchaInputField );

				// Save when pressing 'Enter' in captcha field as it is single line.
				const $captchaInputElement = $( captchaInputField ).find( 'input' );
				$captchaInputElement.on( 'keydown', ( e ) => {
					if ( e.which === OO.ui.Keys.ENTER ) {
						target.saveDialog.executeAction( 'save' );
					}
				} );
				$captchaInputElement.trigger( 'focus' );

				// ProcessDialog's error system isn't great for this yet.
				target.saveDialog.popPending();

				// Emit event for tracking. TODO: This is a bad design
				target.emit( 'saveErrorCaptcha' );
			} )
		).catch( ( error ) => {
			$captchaContainer.append( $( '<span>' ).text( error ) );
			OO.ui.Element.static.scrollIntoView( $captchaContainer[ 0 ] );
		} );
	};

	/**
	 * Just before the save options are fetched for an edit submission,
	 * add the CAPTCHA data to the fields that will be submitted.
	 *
	 * @param {ve.init.Target} target
	 * @return {Promise}
	 */
	ve.init.mw.CaptchaSaveErrorHandler.static.onSaveOptionsProcess = function ( target ) {
		if ( this.captchaWidget === null ) {
			return Promise.resolve();
		}

		return this.captchaWidget.getCaptchaDataForSubmission().then( ( captchaData ) => {
			Object.entries( captchaData ).forEach( ( [ key, value ] ) => {
				target.saveFields[ key ] = () => value;
			} );
		} );
	};

	/**
	 * Initialises the CAPTCHA handler for the current page.
	 */
	ve.init.mw.CaptchaSaveErrorHandler.static.init = function () {
		mw.hook( 've.newTarget' ).add( ( target ) => {
			if ( target.constructor.static.name !== 'article' ) {
				return;
			}
			target.on( 'saveWorkflowEnd', () => {
				this.captchaWidget = null;
			} );
			target.getSaveOptionsProcess().next( () => this.onSaveOptionsProcess( target ) );
		} );
	};

	ve.init.mw.saveErrorHandlerFactory.register( ve.init.mw.CaptchaSaveErrorHandler );
};
