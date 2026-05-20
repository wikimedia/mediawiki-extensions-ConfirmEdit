QUnit.module.if( 'ext.confirmEdit.visualEditor.CaptchaSaveErrorHandler', mw.loader.getState( 'ext.visualEditor.targetLoader' ), QUnit.newMwEnvironment(), () => {

	const captchaSaveErrorHandler = require( 'ext.confirmEdit.visualEditor/ve-confirmedit/ve.init.mw.CaptchaSaveErrorHandler.js' );

	const getMockTarget = ( self, $saveDialogElement ) => ( {
		saveFields: {},
		saveDialog: {
			$element: $saveDialogElement,
			updateSize: self.sandbox.stub(),
			popPending: self.sandbox.stub(),
			clearMessage: self.sandbox.stub(),
			executeAction: self.sandbox.stub(),
			showMessage: ( name, $element ) => {
				$saveDialogElement.append( $element );
			}
		},
		emit: self.sandbox.stub()
	} );

	QUnit.test( 'Successful CAPTCHA render and execution', function ( assert ) {
		const done = assert.async();

		const $captchaInputField = $( '<div>' ).append( '<input>' );
		const updateForCaptchaFailure = this.sandbox.stub().resolves();
		const renderCaptcha = this.sandbox.stub().resolves();
		const getCaptchaDataForSubmission = this.sandbox.stub().resolves( {
			captchaid: 'test',
			captchaword: 'testing'
		} );
		const getInputField = this.sandbox.stub().returns( $captchaInputField[ 0 ] );
		let actualCaptchaWidgetConfig;
		this.sandbox.stub( mw.libs.confirmEdit, 'CaptchaWidget' ).callsFake( function ( config ) {
			actualCaptchaWidgetConfig = config;
			this.updateForCaptchaFailure = updateForCaptchaFailure;
			this.renderCaptcha = renderCaptcha;
			this.getInputField = getInputField;
			this.getCaptchaDataForSubmission = getCaptchaDataForSubmission;
		} );

		captchaSaveErrorHandler();

		const $qunitFixture = $( '#qunit-fixture' );
		const target = getMockTarget( this, $qunitFixture );

		ve.init.mw.CaptchaSaveErrorHandler.static.process(
			{ visualeditoredit: { edit: { captcha: { type: 'question' } } } },
			target
		);

		setTimeout( () => {
			assert.notStrictEqual(
				ve.init.mw.CaptchaSaveErrorHandler.static.captchaWidget,
				null,
				'captchaWidget property should be set after call to process'
			);

			assert.strictEqual(
				actualCaptchaWidgetConfig.interfaceName,
				'visualeditor',
				'Captcha widget is created for the visualeditor interface'
			);
			assert.strictEqual(
				$qunitFixture.find( actualCaptchaWidgetConfig.container ).length,
				1,
				'Save dialog contains the captcha widget container'
			);

			ve.init.mw.CaptchaSaveErrorHandler.static.onSaveOptionsProcess( target ).then( () => {
				assert.deepEqual(
					target.saveFields.captchaword(),
					'testing',
					'captchaword field should be as expected'
				);
				assert.deepEqual(
					target.saveFields.captchaid(),
					'test',
					'captchaid field should be as expected'
				);

				done();
			} ).catch( () => {
				assert.true( false, 'onSaveOptionsProcess should not reject' );

				done();
			} );
		} );
	} );

	QUnit.test( 'Failed render', function ( assert ) {
		const done = assert.async();

		const updateForCaptchaFailure = this.sandbox.stub().resolves();
		const renderCaptcha = this.sandbox.stub().rejects( 'Test error' );
		let actualCaptchaWidgetConfig;
		this.sandbox.stub( mw.libs.confirmEdit, 'CaptchaWidget' ).callsFake( function ( config ) {
			actualCaptchaWidgetConfig = config;
			this.updateForCaptchaFailure = updateForCaptchaFailure;
			this.renderCaptcha = renderCaptcha;
		} );

		captchaSaveErrorHandler();

		const $qunitFixture = $( '#qunit-fixture' );
		const target = getMockTarget( this, $qunitFixture );

		ve.init.mw.CaptchaSaveErrorHandler.static.process(
			{ visualeditoredit: { edit: { captcha: { type: 'simple' } } } },
			target
		);

		setTimeout( () => {
			assert.notStrictEqual(
				ve.init.mw.CaptchaSaveErrorHandler.static.captchaWidget,
				null,
				'captchaWidget property should be set after call to process'
			);

			assert.strictEqual(
				actualCaptchaWidgetConfig.interfaceName,
				'visualeditor',
				'Captcha widget is created for the visualeditor interface'
			);
			const $actualCaptchaContainer = $qunitFixture.find( actualCaptchaWidgetConfig.container );
			assert.strictEqual(
				$actualCaptchaContainer.length,
				1,
				'Save dialog contains the captcha widget container'
			);

			assert.strictEqual(
				$actualCaptchaContainer.text(),
				'Test error',
				'Captcha container shows the error in the rejected promise returned by renderCaptcha'
			);

			done();
		} );
	} );

	QUnit.test( 'onSaveOptionsProcess does nothing if CAPTCHA not rendered', function ( assert ) {
		captchaSaveErrorHandler();

		const $qunitFixture = $( '#qunit-fixture' );
		const target = getMockTarget( this, $qunitFixture );

		return ve.init.mw.CaptchaSaveErrorHandler.static.onSaveOptionsProcess(
			{ visualeditoredit: { edit: { captcha: { type: 'simple' } } } },
			target
		).then( () => {
			assert.deepEqual(
				target.saveFields,
				{},
				'onSaveOptionsProcess does not add save fields if CAPTCHA was not rendered'
			);
		} );
	} );

	QUnit.test.each( 'process() destroys hCaptcha widget when handler is available', {
		'HCaptchaOnLoadHandler is defined': {
			defineHandler: true
		},
		'HCaptchaOnLoadHandler is undefined': {
			defineHandler: false
		}
	}, function ( assert, options ) {
		const destroyWidgetStub = this.sandbox.stub();

		this.sandbox.stub( mw.libs.confirmEdit, 'CaptchaWidget' ).callsFake( function () {
			this.updateForCaptchaFailure = () => Promise.resolve();
			this.renderCaptcha = () => Promise.resolve();
		} );

		const originalHandler = ve.init.mw.HCaptchaOnLoadHandler;
		ve.init.mw.HCaptchaOnLoadHandler = options.defineHandler ?
			{ static: { destroyWidget: destroyWidgetStub } } :
			undefined;

		try {
			captchaSaveErrorHandler();

			const target = getMockTarget( this, $( '#qunit-fixture' ) );

			ve.init.mw.CaptchaSaveErrorHandler.static.process(
				{ visualeditoredit: { edit: { captcha: { type: 'simple' } } } },
				target
			);

			if ( options.defineHandler ) {
				assert.true( destroyWidgetStub.calledOnce, 'destroyWidget should be called once with the target' );
				assert.deepEqual( destroyWidgetStub.getCall( 0 ).args, [ target ],
					'destroyWidget called with target' );
			} else {
				assert.true( destroyWidgetStub.notCalled, 'destroyWidget should not be called when handler is undefined' );
			}
		} finally {
			ve.init.mw.HCaptchaOnLoadHandler = originalHandler;
		}
	} );

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
