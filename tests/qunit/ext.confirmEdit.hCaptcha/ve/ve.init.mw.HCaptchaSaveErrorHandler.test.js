const utils = require( 'ext.confirmEdit.hCaptcha/ext.confirmEdit.hCaptcha/utils.js' );
const hCaptchaSaveErrorHandler = require( 'ext.confirmEdit.hCaptcha/ext.confirmEdit.hCaptcha/ve/ve.init.mw.HCaptchaSaveErrorHandler.js' );

QUnit.module.if( 'ext.confirmEdit.hCaptcha.ve.HCaptchaSaveErrorHandler', mw.loader.getState( 'ext.visualEditor.targetLoader' ), QUnit.newMwEnvironment(), ( hooks ) => {

	const hCaptchaConfig = require( 'ext.confirmEdit.hCaptcha/ext.confirmEdit.hCaptcha/config.json' );

	hooks.beforeEach( function () {
		this.loadHCaptcha = this.sandbox.stub( utils, 'loadHCaptcha' );

		// In a real environment, initPlugins.js does this for us. However, to avoid
		// side effects, we don't use that method of loading the code we are testing.
		// Therefore, run this ourselves.
		require( 'ext.confirmEdit.hCaptcha/ext.confirmEdit.hCaptcha/ve/ve.init.mw.HCaptcha.js' )();
	} );

	QUnit.test.each( 'process uses loadHCaptcha', {
		'hCaptcha is in invisible mode': {
			invisibleMode: true
		},
		'hCaptcha is not in invisible mode': {
			invisibleMode: false
		}
	}, async function ( assert, options ) {
		this.loadHCaptcha.returns( Promise.resolve() );

		hCaptchaConfig.HCaptchaInvisibleMode = options.invisibleMode;

		hCaptchaSaveErrorHandler();

		const $qunitFixture = $( '#qunit-fixture' );
		const target = {
			saveFields: {},
			saveDialog: {
				$element: $qunitFixture,
				updateSize: this.sandbox.stub(),
				popPending: this.sandbox.stub(),
				clearMessage: this.sandbox.stub(),
				showMessage: ( name, $element ) => {
					$qunitFixture.append( $element );
				}
			},
			emit: this.sandbox.stub()
		};
		const mockWindow = {
			hcaptcha: {
				render: this.sandbox.stub()
			}
		};
		mockWindow.hcaptcha.render.returns( 'widget-id' );

		ve.init.mw.HCaptchaSaveErrorHandler.static.window = mockWindow;

		ve.init.mw.HCaptchaSaveErrorHandler.static.process( {}, target );

		setTimeout( () => {
			assert.deepEqual(
				mockWindow.hcaptcha.render.callCount,
				1,
				'window.hcaptcha.render is called once'
			);
			assert.deepEqual(
				ve.init.mw.HCaptchaSaveErrorHandler.static.widgetId,
				'widget-id',
				'widgetId property should be set with the return value of hcaptcha.render'
			);

			// Check that the DOM is as expected
			const $actualHCaptchaContainer = $( '.ext-confirmEdit-visualEditor-hCaptchaContainer', $qunitFixture );
			assert.deepEqual(
				$actualHCaptchaContainer.length,
				1,
				'A hCaptcha container should exist in the DOM'
			);
			assert.deepEqual(
				$( '.ext-confirmEdit-visualEditor-hCaptchaWidgetContainer', $actualHCaptchaContainer ).length,
				1,
				'Only one hCaptcha widget container should exist in the DOM'
			);
			assert.deepEqual(
				$( '.ext-confirmEdit-hcaptcha-privacy-policy', $actualHCaptchaContainer ).length,
				options.invisibleMode ? 1 : 0,
				'hCaptcha privacy policy text should only be added in invisible mode'
			);
			assert.deepEqual(
				$( '.ext-confirmEdit-hcaptcha-visual-editor-error-handler-warning', $actualHCaptchaContainer ).length,
				options.invisibleMode ? 1 : 0,
				'hCaptcha edit notice should only be added in invisible mode'
			);

			assert.true(
				this.loadHCaptcha.calledOnce,
				'loadHCaptcha is called when by process'
			);
			assert.deepEqual(
				this.loadHCaptcha.firstCall.args,
				[ window, 'visualeditor', { render: 'explicit' } ],
				'loadHCaptcha arguments are as expected'
			);
		} );
	} );

	QUnit.test.each( 'matchFunction correctly matches', {
		'Captcha is not present': {
			data: { visualeditoredit: { edit: {} } },
			expected: false,
			assertMessage: 'Should not match if captcha is not present in data'
		},
		'Captcha is present, but shown captcha is FancyCaptcha': {
			data: { visualeditoredit: { edit: { captcha: { type: 'fancycaptcha' } } } },
			expected: false,
			assertMessage: 'Should not match if the captcha is FancyCaptcha'
		},
		'hCaptcha captcha is present': {
			data: { visualeditoredit: { edit: { captcha: { type: 'hcaptcha' } } } },
			expected: true,
			assertMessage: 'Should match if the captcha is hCaptcha'
		}
	}, ( assert, options ) => {
		hCaptchaSaveErrorHandler();

		assert.deepEqual(
			ve.init.mw.HCaptchaSaveErrorHandler.static.matchFunction( options.data ),
			options.expected,
			options.assertMessage
		);
	} );
} );
