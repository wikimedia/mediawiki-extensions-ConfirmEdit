const hCaptchaUtils = require( 'ext.confirmEdit.hCaptcha/ext.confirmEdit.hCaptcha/utils.js' );
const hCaptchaOnLoadHandler = require( 'ext.confirmEdit.hCaptcha/ext.confirmEdit.hCaptcha/ve/ve.init.mw.HCaptchaOnLoadHandler.js' );

QUnit.module( 'ext.confirmEdit.hCaptcha.ve.HCaptchaOnLoadHandler', QUnit.newMwEnvironment( {
	beforeEach() {
		this.loadHCaptcha = this.sandbox.stub( hCaptchaUtils, 'loadHCaptcha' );

		this.origVisualEditorSurface = ve.init.target.surface;
		ve.init.target.surface = {};

		// In a real environment, initPlugins.js does this for us. However, to avoid
		// side effects, we don't use that method of loading the code we are testing.
		// Therefore, run this ourselves.
		require( 'ext.confirmEdit.hCaptcha/ext.confirmEdit.hCaptcha/ve/ve.init.mw.HCaptcha.js' )();
	},
	afterEach() {
		this.loadHCaptcha.restore();

		ve.init.target.surface = this.origVisualEditorSurface;
	}
} ) );

QUnit.test( 'transact event in VisualEditor surface causes hCaptcha load once', function ( assert ) {
	mw.config.set( 'wgConfirmEditCaptchaNeededForGenericEdit', 'hcaptcha' );
	this.loadHCaptcha.returns( Promise.resolve() );

	const fakeDocument = new OO.EventEmitter();

	// Mock the surface to allow the code we are testing to interact with
	// the fake VisualEditor editor document created above
	ve.init.target.surface = {
		getModel: () => ( {
			getDocument: () => fakeDocument
		} )
	};

	hCaptchaOnLoadHandler();

	ve.init.mw.HCaptchaOnLoadHandler.static.onActivationComplete();

	assert.true(
		this.loadHCaptcha.notCalled,
		'loadHCaptcha is not called before transact event is fired'
	);

	// Trigger the transact event multiple times so we can test loading hCaptcha only happens
	// once for all of these events
	fakeDocument.emit( 'transact' );
	fakeDocument.emit( 'transact' );
	fakeDocument.emit( 'transact' );

	assert.true(
		this.loadHCaptcha.calledOnce,
		'loadHCaptcha is called once after transact event is fired'
	);
	assert.deepEqual(
		this.loadHCaptcha.firstCall.args,
		[ window, 'visualeditor', { render: 'explicit' } ],
		'loadHCaptcha arguments are as expected'
	);
} );

QUnit.test.each( 'shouldRun correctly matches', {
	'wgConfirmEditCaptchaNeededForGenericEdit is undefined': {
		configVariableValue: undefined,
		expected: false
	},
	'wgConfirmEditCaptchaNeededForGenericEdit is false': {
		configVariableValue: false,
		expected: false
	},
	'wgConfirmEditCaptchaNeededForGenericEdit is fancycaptcha': {
		configVariableValue: 'fancycaptcha',
		expected: false
	},
	'wgConfirmEditCaptchaNeededForGenericEdit is hcaptcha': {
		configVariableValue: 'hcaptcha',
		expected: true
	}
}, ( assert, options ) => {
	mw.config.set( 'wgConfirmEditCaptchaNeededForGenericEdit', options.configVariableValue );

	hCaptchaOnLoadHandler();

	assert.deepEqual(
		ve.init.mw.HCaptchaOnLoadHandler.static.shouldRun(),
		options.expected,
		'::shouldRun returns expected value'
	);
} );
