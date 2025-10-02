const utils = require( 'ext.confirmEdit.hCaptcha/ext.confirmEdit.hCaptcha/utils.js' );
const hCaptchaSaveErrorHandler = require( 'ext.confirmEdit.hCaptcha/ext.confirmEdit.hCaptcha/ve/ve.init.mw.HCaptchaSaveErrorHandler.js' );

QUnit.module( 'ext.confirmEdit.hCaptcha.ve.HCaptchaSaveErrorHandler', QUnit.newMwEnvironment( {
	beforeEach() {
		this.loadHCaptcha = this.sandbox.stub( utils, 'loadHCaptcha' );
	},
	afterEach() {
		this.loadHCaptcha.restore();
	}
} ) );

QUnit.test( 'getReadyPromise uses loadHCaptcha but only calls it once', function ( assert ) {
	this.loadHCaptcha.returns( Promise.resolve() );
	hCaptchaSaveErrorHandler();

	const actualFirstReadyPromise = ve.init.mw.HCaptchaSaveErrorHandler.static.getReadyPromise();
	const actualSecondReadyPromise = ve.init.mw.HCaptchaSaveErrorHandler.static.getReadyPromise();

	assert.true(
		this.loadHCaptcha.calledOnce,
		'loadHCaptcha is called when getReadyPromise is called'
	);
	assert.deepEqual(
		this.loadHCaptcha.firstCall.args,
		[ window, { render: 'explicit' } ],
		'loadHCaptcha arguments are as expected'
	);

	assert.deepEqual(
		actualFirstReadyPromise, actualSecondReadyPromise,
		'Uses the same promise object for both calls, because loadHCaptcha should be called once'
	);
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
