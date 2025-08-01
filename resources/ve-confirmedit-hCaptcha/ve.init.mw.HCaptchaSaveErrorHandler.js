const config = require( './config.json' );

mw.loader.using( 'ext.visualEditor.targetLoader' ).then( () => {
	mw.libs.ve.targetLoader.addPlugin( () => {
		ve.init.mw.HCaptchaSaveErrorHandler = function () {};

		OO.inheritClass( ve.init.mw.HCaptchaSaveErrorHandler, ve.init.mw.SaveErrorHandler );

		ve.init.mw.HCaptchaSaveErrorHandler.static.name = 'confirmEditHCaptcha';

		ve.init.mw.HCaptchaSaveErrorHandler.static.getReadyPromise = function () {
			if ( !this.readyPromise ) {
				const deferred = $.Deferred();
				const scriptURL = new URL( config.HCaptchaApiUrl, location.href );
				const onLoadFn = 'onHcaptchaLoadCallback' + Date.now();
				scriptURL.searchParams.set( 'onload', onLoadFn );
				scriptURL.searchParams.set( 'render', 'explicit' );

				this.readyPromise = deferred.promise();
				window[ onLoadFn ] = deferred.resolve;
				mw.loader.load( scriptURL.toString() );
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
	} );
} );
