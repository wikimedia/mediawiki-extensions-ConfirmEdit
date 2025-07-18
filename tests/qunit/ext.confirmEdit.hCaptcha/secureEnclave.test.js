const useSecureEnclave = require( 'ext.confirmEdit.hCaptcha/ext.confirmEdit.hCaptcha/secureEnclave.js' );
const config = require( 'ext.confirmEdit.hCaptcha/ext.confirmEdit.hCaptcha/config.json' );

QUnit.module( 'ext.confirmEdit.hCaptcha.secureEnclave', QUnit.newMwEnvironment( {
	beforeEach() {
		this.getScript = this.sandbox.stub( mw.loader, 'getScript' );
		this.track = this.sandbox.stub( mw, 'track' );

		// Sinon fake timers as of v21 only return a static fake value from performance.measure(),
		// so use a regular stub instead.
		this.measure = this.sandbox.stub( performance, 'measure' );
		this.measure.returns( { duration: 0 } );

		this.window = {
			hcaptcha: {
				render: this.sandbox.stub(),
				execute: this.sandbox.stub()
			}
		};

		const form = document.createElement( 'form' );
		this.submit = this.sandbox.stub( form, 'submit' );

		this.$form = $( form )
			.append( '<input type="text" name="some-input" />' )
			.append( '<input type="hidden" id="h-captcha">' )
			.append( '<input type="hidden" id="h-captcha-response">' );

		this.$form.appendTo( $( '#qunit-fixture' ) );

		this.isLoadingIndicatorVisible = () => this.$form
			.find( '.ext-confirmEdit-hCaptchaLoadingIndicator' )
			.css( 'display' ) !== 'none';

		this.origUrl = config.HCaptchaApiUrl;
		config.HCaptchaApiUrl = 'https://example.com/hcaptcha.js';
	},

	afterEach() {
		this.getScript.restore();
		this.track.restore();
		this.measure.restore();

		config.HCaptchaApiUrl = this.origUrl;
	}
} ) );

QUnit.test( 'should not load hCaptcha before the form has been interacted with', async function ( assert ) {
	useSecureEnclave( this.window );

	assert.true( this.getScript.notCalled, 'should not load hCaptcha SDK' );
	assert.true( this.window.hcaptcha.render.notCalled, 'should not render hCaptcha' );
	assert.true( this.window.hcaptcha.execute.notCalled, 'should not execute hCaptcha' );
	assert.true( this.track.notCalled, 'should not emit hCaptcha performance events' );
} );

QUnit.test( 'should load hCaptcha exactly once when the form is interacted with', async function ( assert ) {
	useSecureEnclave( this.window );

	const $input = this.$form.find( '[name=some-input]' );

	$input.trigger( 'focus' );
	$input.trigger( 'input' );
	$input.trigger( 'input' );

	// Wait one tick for event handlers to run.
	await new Promise( ( resolve ) => {
		setTimeout( resolve );
	} );

	assert.true( this.getScript.calledOnce, 'should load hCaptcha SDK once' );
} );

QUnit.test( 'should load hCaptcha on form submissions triggered before hCaptcha was setup', async function ( assert ) {
	useSecureEnclave( this.window );

	this.$form.trigger( 'submit' );

	// Wait one tick for event handlers to run.
	await new Promise( ( resolve ) => {
		setTimeout( resolve );
	} );

	assert.true( this.getScript.calledOnce, 'should load hCaptcha SDK once' );
	assert.true( this.submit.notCalled, 'form submission should have been prevented' );
} );

QUnit.test( 'should intercept form submissions', function ( assert ) {
	this.getScript.callsFake( async () => {
		assert.true( this.isLoadingIndicatorVisible(), 'should show loading indicator' );
		this.window.onHCaptchaSDKLoaded();
	} );
	this.window.hcaptcha.render.returns( 'some-captcha-id' );
	this.window.hcaptcha.execute.callsFake( async () => {
		assert.true( this.isLoadingIndicatorVisible(), 'loading indicator should be visible until hCaptcha finishes' );
		return { response: 'some-token' };
	} );

	const result = useSecureEnclave( this.window )
		.then( () => {
			assert.true( this.getScript.calledOnce, 'should load hCaptcha SDK once' );
			assert.deepEqual(
				this.getScript.firstCall.args,
				[ 'https://example.com/hcaptcha.js?onload=onHCaptchaSDKLoaded' ],
				'should load hCaptcha SDK from given URL'
			);

			assert.false( this.isLoadingIndicatorVisible(), 'should hide loading indicator' );

			assert.true( this.window.hcaptcha.render.calledOnce, 'should render hCaptcha widget once' );
			assert.deepEqual(
				this.window.hcaptcha.render.firstCall.args,
				[ 'h-captcha' ],
				'should render hCaptcha widget in correct element'
			);

			assert.true( this.window.hcaptcha.execute.calledOnce, 'should run hCaptcha once' );
			assert.deepEqual(
				this.window.hcaptcha.execute.firstCall.args,
				[ 'some-captcha-id', { async: true } ],
				'should invoke hCaptcha with correct ID'
			);

			assert.true( this.submit.calledOnce, 'should submit form once hCaptcha token is available' );
			assert.strictEqual(
				this.$form.find( '#h-captcha-response' ).val(),
				'some-token',
				'should add hCaptcha response token to form'
			);

			assert.strictEqual(
				this.$form.find( '.cdx-message' ).css( 'display' ),
				'none',
				'no error message should be shown'
			);
			assert.strictEqual(
				this.$form.find( '.cdx-message' ).text(),
				'',
				'no error message should be set'
			);
		} );

	this.$form.find( '[name=some-input]' ).trigger( 'input' );
	this.$form.trigger( 'submit' );

	return result;
} );

QUnit.test( 'should measure hCaptcha load and execute timing', function ( assert ) {
	this.measure
		.onFirstCall().returns( { duration: 1718 } )
		.onSecondCall().returns( { duration: 2314 } );

	this.getScript.callsFake( async () => {
		this.window.onHCaptchaSDKLoaded();
	} );
	this.window.hcaptcha.render.returns( 'some-captcha-id' );
	this.window.hcaptcha.execute.callsFake( async () => ( { response: 'some-token' } ) );

	const result = useSecureEnclave( this.window )
		.then( () => {
			assert.strictEqual( this.track.callCount, 4, 'should invoke mw.track() four times' );
			assert.deepEqual(
				this.track.firstCall.args,
				[ 'specialCreateAccount.performanceTiming', 'hcaptcha-load', 1.718 ],
				'should emit event for load time'
			);
			assert.deepEqual(
				this.track.secondCall.args,
				[ 'mediawiki_special_createaccount_hcaptcha_load_duration_seconds', 1718 ],
				'should record metric for load time'
			);
			assert.deepEqual(
				this.track.thirdCall.args,
				[ 'specialCreateAccount.performanceTiming', 'hcaptcha-execute', 2.314 ],
				'should emit event for execution time'
			);
			assert.deepEqual(
				this.track.lastCall.args,
				[ 'mediawiki_special_createaccount_hcaptcha_execute_duration_seconds', 2314 ],
				'should record metric for execution time'
			);
		} );

	this.$form.find( '[name=some-input]' ).trigger( 'input' );
	this.$form.trigger( 'submit' );

	return result;
} );

QUnit.test( 'should surface load errors as soon as possible', async function ( assert ) {
	this.getScript.callsFake( () => {
		assert.true( this.isLoadingIndicatorVisible(), 'should show loading indicator' );
		return Promise.reject();
	} );

	const hCaptchaResult = useSecureEnclave( this.window );

	this.$form.find( '[name=some-input]' ).trigger( 'input' );

	await hCaptchaResult;

	assert.false( this.isLoadingIndicatorVisible(), 'should hide loading indicator' );

	assert.notStrictEqual(
		this.$form.find( '.cdx-message' ).css( 'display' ),
		'none',
		'error message container should be visible'
	);
	assert.strictEqual(
		this.$form.find( '.cdx-message' ).text(),
		'(hcaptcha-load-error)',
		'load error message should be set'
	);
} );

QUnit.test( 'should surface irrecoverable workflow execution errors as soon as possible', async function ( assert ) {
	this.getScript.callsFake( async () => {
		assert.true( this.isLoadingIndicatorVisible(), 'should show loading indicator' );
		this.window.onHCaptchaSDKLoaded();
	} );
	this.window.hcaptcha.render.returns( 'some-captcha-id' );
	this.window.hcaptcha.execute.callsFake( () => {
		assert.true( this.isLoadingIndicatorVisible(), 'loading indicator should be visible until hCaptcha finishes' );
		return Promise.reject( 'rate-limited' );
	} );

	const hCaptchaResult = useSecureEnclave( this.window );

	this.$form.find( '[name=some-input]' ).trigger( 'input' );

	await hCaptchaResult;

	assert.false( this.isLoadingIndicatorVisible(), 'should hide loading indicator' );

	assert.notStrictEqual(
		this.$form.find( '.cdx-message' ).css( 'display' ),
		'none',
		'error message container should be visible'
	);
	assert.strictEqual(
		this.$form.find( '.cdx-message' ).text(),
		'(hcaptcha-rate-limited)',
		'error message should be set'
	);
} );

QUnit.test( 'should surface recoverable workflow execution errors on submit', function ( assert ) {
	this.getScript.callsFake( async () => {
		assert.true( this.isLoadingIndicatorVisible(), 'should show loading indicator' );
		this.window.onHCaptchaSDKLoaded();
	} );

	this.window.hcaptcha.render.returns( 'some-captcha-id' );
	this.window.hcaptcha.execute.callsFake( () => {
		assert.true( this.isLoadingIndicatorVisible(), 'loading indicator should be visible until hCaptcha finishes' );
		return Promise.reject( 'challenge-closed' );
	} );

	useSecureEnclave( this.window );

	const formSubmitted = new Promise( ( resolve ) => {
		this.$form.one( 'submit', () => setTimeout( resolve ) );
	} );

	this.$form.find( '[name=some-input]' ).trigger( 'input' );
	this.$form.trigger( 'submit' );

	return formSubmitted.then( () => {
		assert.false( this.isLoadingIndicatorVisible(), 'should hide loading indicator' );

		assert.true( this.submit.notCalled, 'submit should have been prevented' );

		assert.notStrictEqual(
			this.$form.find( '.cdx-message' ).css( 'display' ),
			'none',
			'error message container should be visible'
		);
		assert.strictEqual(
			this.$form.find( '.cdx-message' ).text(),
			'(hcaptcha-challenge-closed)',
			'error message should be set'
		);
	} );
} );

QUnit.test( 'should allow recovering from a recoverable error by starting a new workflow', function ( assert ) {
	this.getScript.callsFake( async () => {
		assert.true( this.isLoadingIndicatorVisible(), 'should show loading indicator' );
		this.window.onHCaptchaSDKLoaded();
	} );

	this.window.hcaptcha.render.returns( 'some-captcha-id' );
	this.window.hcaptcha.execute
		.onFirstCall().returns( Promise.reject( 'challenge-closed' ) )
		.onSecondCall().resolves( { response: 'some-token' } );

	const result = useSecureEnclave( this.window )
		.then( () => {
			assert.false( this.isLoadingIndicatorVisible(), 'should hide loading indicator' );

			assert.true( this.window.hcaptcha.render.calledOnce, 'should render hCaptcha widget once' );
			assert.deepEqual(
				this.window.hcaptcha.render.firstCall.args,
				[ 'h-captcha' ],
				'should render hCaptcha widget in correct element'
			);

			assert.true( this.window.hcaptcha.execute.calledTwice, 'should run hCaptcha twice' );
			assert.deepEqual(
				this.window.hcaptcha.execute.firstCall.args,
				[ 'some-captcha-id', { async: true } ],
				'should invoke hCaptcha with correct ID'
			);
			assert.deepEqual(
				this.window.hcaptcha.execute.secondCall.args,
				[ 'some-captcha-id', { async: true } ],
				'should invoke hCaptcha with correct ID'
			);

			assert.true( this.submit.calledOnce, 'submit should have eventually succeeded' );
			assert.strictEqual(
				this.$form.find( '#h-captcha-response' ).val(),
				'some-token',
				'should add hCaptcha response token to form'
			);

			assert.strictEqual(
				this.$form.find( '.cdx-message' ).css( 'display' ),
				'none',
				'no error message should be shown'
			);
		} );

	this.$form.find( '[name=some-input]' ).trigger( 'input' );

	this.$form.one( 'submit', () => setTimeout( () => this.$form.trigger( 'submit' ) ) );

	this.$form.trigger( 'submit' );

	return result;
} );
