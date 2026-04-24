'use strict';

QUnit.module( 'ext.confirmEdit.CaptchaWidget', QUnit.newMwEnvironment(), () => {
	QUnit.test( 'captchaNeededForEdit returns value of wgConfirmEditCaptchaNeededForGenericEdit', ( assert ) => {
		mw.config.set( 'wgConfirmEditCaptchaNeededForGenericEdit', 'captcha' );

		assert.strictEqual(
			mw.libs.confirmEdit.CaptchaWidget.static.captchaNeededForEdit(),
			'captcha',
			'captchaNeededForEdit should return the value of wgConfirmEditCaptchaNeededForGenericEdit'
		);
	} );

	QUnit.test( 'constructor called without container provided', ( assert ) => {
		assert.throws(
			() => {
				// eslint-disable-next-line no-new
				new mw.libs.confirmEdit.CaptchaWidget( {} );
			},
			/CaptchaWidget requires a container/,
			'Construction without container provided should throw error'
		);
	} );

	QUnit.test( 'constructor makes CAPTCHA type lowercase', ( assert ) => {
		const captchaWidget = new mw.libs.confirmEdit.CaptchaWidget( {
			container: '#qunit-fixture',
			type: 'Test-Captcha'
		} );

		assert.strictEqual(
			captchaWidget.config.type,
			'test-captcha',
			'CAPTCHA type should be lowercase in internal config'
		);
	} );

	QUnit.test( 'renderCaptcha rejects if CAPTCHA type not specified', ( assert ) => {
		const captchaWidget = new mw.libs.confirmEdit.CaptchaWidget( {
			container: '#qunit-fixture'
		} );

		assert.rejects(
			captchaWidget.renderCaptcha(),
			/CAPTCHA type not specified/,
			'getCaptchaDataForSubmission should reject if the CAPTCHA type has not been specified'
		);
	} );

	QUnit.test( 'renderCaptcha rejects if CAPTCHA not supported', ( assert ) => {
		const $qunitFixture = $( '#qunit-fixture' );

		const captchaWidget = new mw.libs.confirmEdit.CaptchaWidget( {
			type: 'test-captcha',
			container: $qunitFixture[ 0 ]
		} );

		assert.rejects(
			captchaWidget.renderCaptcha(),
			/CAPTCHA not supported/,
			'getCaptchaDataForSubmission should reject if the CAPTCHA has not been rendered'
		);
		assert.strictEqual(
			$qunitFixture.find( '.mw-confirmEdit-captchaWidget' ).length,
			1,
			'Only one captcha widget container should exist'
		);
	} );

	QUnit.test( 'renderCaptcha rejects if container does not exist in the DOM', ( assert ) => {
		const captchaWidget = new mw.libs.confirmEdit.CaptchaWidget( {
			type: 'test-captcha',
			container: $( '<div>' )[ 0 ]
		} );

		assert.rejects(
			captchaWidget.renderCaptcha(),
			/CAPTCHA container should exist in the DOM before calling renderCaptcha/,
			'renderCaptcha should reject if the CAPTCHA container is not in the DOM'
		);
	} );

	QUnit.test( 'getCaptchaDataForSubmission rejects if CAPTCHA not rendered', ( assert ) => {
		const captchaWidget = new mw.libs.confirmEdit.CaptchaWidget( {
			type: 'test-captcha',
			container: '#qunit-fixture'
		} );

		assert.rejects(
			captchaWidget.getCaptchaDataForSubmission(),
			/Render the CAPTCHA before getting the CAPTCHA data/,
			'getCaptchaDataForSubmission should reject if the CAPTCHA has not been rendered'
		);
	} );

	QUnit.test( 'getCaptchaDataForSubmission rejects if CAPTCHA not supported', ( assert ) => {
		const captchaWidget = new mw.libs.confirmEdit.CaptchaWidget( {
			type: 'test-captcha',
			container: '#qunit-fixture'
		} );
		captchaWidget.captchaRendered = true;

		assert.rejects(
			captchaWidget.getCaptchaDataForSubmission(),
			/CAPTCHA not supported/,
			'getCaptchaDataForSubmission should reject if the CAPTCHA is not supported'
		);
	} );

	QUnit.test( 'updateForCaptchaFailure resolves if no re-rendering needed', function ( assert ) {
		const captchaWidget = new mw.libs.confirmEdit.CaptchaWidget( {
			type: 'test-captcha',
			container: '#qunit-fixture'
		} );

		captchaWidget.renderCaptcha = this.sandbox.stub();

		return captchaWidget.updateForCaptchaFailure( { type: 'Test-captcha' } ).then( () => {
			assert.true(
				captchaWidget.renderCaptcha.notCalled,
				'renderCaptcha should not be called if no re-rendering was needed'
			);
			assert.strictEqual(
				captchaWidget.config.type,
				'test-captcha',
				'Internal CAPTCHA type should be in lowercase'
			);
		} );
	} );

	QUnit.test( 'updateForCaptchaFailure re-renders the CAPTCHA if captcha changed', function ( assert ) {
		const captchaWidget = new mw.libs.confirmEdit.CaptchaWidget( {
			type: 'test-captcha',
			container: '#qunit-fixture'
		} );

		captchaWidget.captchaRendered = true;
		captchaWidget.renderCaptcha = this.sandbox.stub().returns( Promise.resolve() );

		return captchaWidget.updateForCaptchaFailure( { type: 'test-Captcha-2' } ).then( () => {
			assert.strictEqual(
				captchaWidget.renderCaptcha.callCount,
				1,
				'renderCaptcha should be called once if the CAPTCHA type has changed'
			);
			assert.strictEqual(
				captchaWidget.config.type,
				'test-captcha-2',
				'Internal CAPTCHA type should be in lowercase'
			);
		} );
	} );
} );
