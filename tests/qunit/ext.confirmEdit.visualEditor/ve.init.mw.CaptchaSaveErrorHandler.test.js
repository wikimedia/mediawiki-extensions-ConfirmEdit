QUnit.module.if( 'ext.confirmEdit.visualEditor.CaptchaSaveErrorHandler', mw.loader.getState( 'ext.visualEditor.targetLoader' ), QUnit.newMwEnvironment(), () => {

	const captchaSaveErrorHandler = require( 'ext.confirmEdit.visualEditor/ve-confirmedit/ve.init.mw.CaptchaSaveErrorHandler.js' );

	QUnit.test.each( 'matchFunction correctly matches', {
		'Captcha is not present': {
			data: { visualeditoredit: { edit: {} } },
			expected: false,
			assertMessage: 'Should not match if captcha is not present in data'
		},
		'Captcha is present, but shown captcha is hCaptcha': {
			data: { visualeditoredit: { edit: { captcha: { type: 'hcaptcha' } } } },
			expected: false,
			assertMessage: 'Should not match if the captcha is HCaptcha'
		},
		'FancyCaptcha is present in response': {
			data: { visualeditoredit: { edit: { captcha: { type: 'image', url: 'test' } } } },
			expected: true,
			assertMessage: 'Should match if the captcha is FancyCaptcha'
		},
		'QuestyCaptcha is present in response': {
			data: { visualeditoredit: { edit: { captcha: { type: 'question' } } } },
			expected: true,
			assertMessage: 'Should match if the captcha is FancyCaptcha'
		},
		'SimpleCaptcha is present in response': {
			data: { visualeditoredit: { edit: { captcha: { type: 'simple' } } } },
			expected: true,
			assertMessage: 'Should match if the captcha is FancyCaptcha'
		}
	}, ( assert, options ) => {
		captchaSaveErrorHandler();

		assert.deepEqual(
			ve.init.mw.CaptchaSaveErrorHandler.static.matchFunction( options.data ),
			options.expected,
			options.assertMessage
		);
	} );
} );
