const useSecureEnclave = require( 'ext.confirmEdit.hCaptcha/ext.confirmEdit.hCaptcha/secureEnclave.js' );
const config = require( 'ext.confirmEdit.hCaptcha/ext.confirmEdit.hCaptcha/config.json' );

/**
 * Introduces a delay which allows pending actions to be processed.
 *
 * By default, the delay takes one event cycle of the Javascript engine.
 *
 * This is used in order to have a chance for event handlers to run before
 * asserting their side effects.
 *
 * @param {number} timeout Number of milliseconds to wait.
 * @return {Promise<void>}
 */
const delay = ( timeout = 0 ) => new Promise( ( resolve ) => {
	setTimeout( resolve, timeout );
} );

QUnit.module( 'ext.confirmEdit.hCaptcha.secureEnclave', QUnit.newMwEnvironment( {
	beforeEach() {
		mw.config.set( 'wgDBname', 'testwiki' );
		mw.config.set( 'wgHCaptchaTriggerFormSubmission', null );

		// Prevent tests from keeping trying to load the hCaptcha script when
		// testing the "onerror" logic.
		mw.config.set( 'wgHCaptchaMaxLoadAttempts', 2 );
		mw.config.set( 'wgHCaptchaBaseRetryDelay', 1 );

		this.track = this.sandbox.stub( mw, 'track' );
		this.logError = this.sandbox.stub( mw.errorLogger, 'logError' );

		// Sinon fake timers as of v21 only return a static fake value from performance.measure(),
		// so use a regular stub instead.
		this.measure = this.sandbox.stub( performance, 'measure' );
		this.measure.returns( { duration: 0 } );

		this.getEntriesByName = this.sandbox.stub( performance, 'getEntriesByName' );

		// We do not want to add real script elements to the page or interact with the real
		// hcaptcha, so stub the code that does this for this test
		this.window = {
			hcaptcha: {
				render: this.sandbox.stub(),
				execute: this.sandbox.stub()
			},
			document: {
				createElement: this.sandbox.stub().returns( {
					classList: {
						contains: this.sandbox.stub().returns( false )
					}
				} ),
				head: {
					appendChild: this.sandbox.stub(),
					removeChild: this.sandbox.stub()
				},
				querySelectorAll: this.sandbox.stub().returns( [] )
			},
			performance: {
				measure: this.measure,
				getEntriesByName: this.getEntriesByName,
				now: this.sandbox.stub().returns( 1234 )
			},
			location: { hostname: 'test.example' },
			navigator: { connection: { effectiveType: '4g' } }
		};

		const form = document.createElement( 'form' );
		this.submit = this.sandbox.stub( form, 'submit' );

		this.$form = $( form )
			.append( '<input type="text" name="some-input" />' )
			.append( '<textarea name="some-textarea"></textarea>' )
			.append( '<input type="hidden" id="h-captcha">' )
			.append( '<input type="submit" value="Save Changes" id="wpSave">' )
			.append( '<div id="wpSaveWidget"></div>' );

		this.sandbox.stub( window.OO.ui, 'infuse' ).returns( {
			setDisabled: ( disabled ) => {
				this.$form.find( 'input[type="submit"], button[type="submit"]' ).prop( 'disabled', disabled );
			}
		} );

		this.$form.appendTo( $( '#qunit-fixture' ) );

		// The display property may be "none" if the element is hidden, but it
		// can also not be set at all (i.e. undefined) if the loading indicator
		// was never added to the DOM in the first place. When the element is
		// being shown, its display property may be either "block" or "flex".
		this.isLoadingIndicatorVisible = () => !(
			[ undefined, 'none' ].includes(
				this.$form
					.find( '.ext-confirmEdit-hCaptchaLoadingIndicator' )
					.css( 'display' )
			)
		);

		this.areSubmitButtonsDisabled = () => {
			const $submitButtons = this.$form.find( 'input[type="submit"], button[type="submit"]' );
			return $submitButtons.length > 0 && $submitButtons.toArray().every( ( btn ) => btn.disabled );
		};

		this.origUrl = config.HCaptchaApiUrl;
		this.origIntegrityHash = config.HCaptchaApiUrlIntegrityHash;
		config.HCaptchaApiUrl = 'https://example.com/hcaptcha.js';
		config.HCaptchaApiUrlIntegrityHash = '1234abcef';

		// Needed so we are able to reset the internal state between tests
		// (i.e state of widgets that may've been set by previous tests)
		this.utils = require( 'ext.confirmEdit.hCaptcha/ext.confirmEdit.hCaptcha/utils.js' );
	},

	afterEach() {
		this.track.restore();
		this.measure.restore();
		this.logError.restore();
		this.getEntriesByName.restore();

		this.utils.reset();

		config.HCaptchaApiUrl = this.origUrl;
		config.HCaptchaApiUrlIntegrityHash = this.origIntegrityHash;
	}
} ) );

QUnit.test( 'should not load hCaptcha before the form has been interacted with', async function ( assert ) {
	useSecureEnclave( this.window );

	assert.true( this.window.document.head.appendChild.notCalled, 'should not load hCaptcha SDK' );
	assert.true( this.window.hcaptcha.render.notCalled, 'should not render hCaptcha' );
	assert.true( this.window.hcaptcha.execute.notCalled, 'should not execute hCaptcha' );
	assert.true( this.track.notCalled, 'should not emit hCaptcha performance events' );
} );

QUnit.test.each( 'should load hCaptcha exactly once when the form is interacted with', {
	'interaction with input element': {
		fieldName: 'some-textarea'
	},
	'interaction with textarea element': {
		fieldName: 'some-textarea'
	}
}, async function ( assert, data ) {
	this.window.document.head.appendChild.callsFake( () => {
		this.window.onHCaptchaSDKLoaded();
	} );

	useSecureEnclave( this.window );

	const $field = this.$form.find( '[name=' + data.fieldName + ']' );

	$field.trigger( 'focus' );
	$field.trigger( 'input' );
	$field.trigger( 'input' );

	await delay();

	assert.true( this.window.document.head.appendChild.calledOnce, 'should load hCaptcha SDK once' );
	assert.true( this.window.hcaptcha.render.calledOnce, 'should render hCaptcha widget once' );
	assert.deepEqual(
		this.window.hcaptcha.render.firstCall.args[ 0 ],
		'h-captcha',
		'should render hCaptcha widget in correct element'
	);
	assert.true( this.window.hcaptcha.execute.notCalled, 'should not execute hCaptcha before the form is submitted' );
} );

QUnit.test( 'should load hCaptcha on form submissions triggered before hCaptcha was setup', async function ( assert ) {
	this.window.document.head.appendChild.callsFake( () => {
		this.window.onHCaptchaSDKLoaded();
	} );

	useSecureEnclave( this.window );

	this.$form.trigger( 'submit' );

	await delay();

	assert.true( this.window.document.head.appendChild.calledOnce, 'should load hCaptcha SDK once' );
	assert.true( this.submit.notCalled, 'form submission should have been prevented' );
	assert.true( this.window.hcaptcha.render.calledOnce, 'should render hCaptcha widget once' );
	assert.deepEqual(
		this.window.hcaptcha.render.firstCall.args[ 0 ],
		'h-captcha',
		'should render hCaptcha widget in correct element'
	);
	assert.true( this.window.hcaptcha.execute.notCalled, 'should not execute hCaptcha before the form is submitted' );
} );

function assertHCaptchaWasExecuted( assert, testcase ) {
	const win = testcase.window;

	assert.true( win.document.head.appendChild.calledOnce, 'should load hCaptcha SDK once' );
	const actualScriptElement = win.document.head.appendChild.firstCall.args[ 0 ];
	assert.deepEqual(
		actualScriptElement.src,
		'https://example.com/hcaptcha.js?onload=onHCaptchaSDKLoaded',
		'should load hCaptcha SDK from given URL'
	);
	assert.deepEqual(
		actualScriptElement.integrity,
		'1234abcef',
		'should load hCaptcha SDK from given URL'
	);

	assert.false(
		testcase.isLoadingIndicatorVisible(),
		'should hide loading indicator'
	);
	assert.false(
		testcase.areSubmitButtonsDisabled(),
		'submit buttons should be re-enabled after success'
	);

	assert.true( win.hcaptcha.render.calledOnce, 'should render hCaptcha widget once' );
	assert.deepEqual(
		win.hcaptcha.render.firstCall.args[ 0 ],
		'h-captcha',
		'should render hCaptcha widget in correct element'
	);

	assert.true( win.hcaptcha.execute.calledOnce, 'should run hCaptcha once' );
	assert.deepEqual(
		win.hcaptcha.execute.firstCall.args,
		[ 'some-captcha-id', { async: true } ],
		'should invoke hCaptcha with correct ID'
	);
}

function assertSubmissionDone( assert, testcase, token = 'some-token', callCount = 1 ) {
	assert.strictEqual(
		testcase.submit.callCount, callCount,
		'should submit form once hCaptcha token is available'
	);
	assert.strictEqual(
		testcase.$form.find( '#h-captcha-response' ).val(),
		token,
		'should add hCaptcha response token to form'
	);
	assert.strictEqual(
		testcase.$form.find( '.cdx-message' ).css( 'display' ),
		'none',
		'no error message should be shown'
	);
	assert.strictEqual(
		testcase.$form.find( '.cdx-message' ).text(),
		'',
		'no error message should be set'
	);
}

QUnit.test( 'should intercept form submissions by default', async function ( assert ) {
	this.window.document.head.appendChild.callsFake( () => {
		assert.false( this.isLoadingIndicatorVisible(), 'should not show loading indicator prior to execute' );
		this.window.onHCaptchaSDKLoaded();
	} );
	this.window.hcaptcha.render.returns( 'some-captcha-id' );
	this.window.hcaptcha.execute.callsFake( async () => {
		assert.true( this.isLoadingIndicatorVisible(), 'loading indicator should be visible during execute' );
		assert.true( this.areSubmitButtonsDisabled(), 'submit buttons should be disabled during execute' );
		return { response: 'some-token' };
	} );

	const result = useSecureEnclave( this.window ).then(
		() => assertHCaptchaWasExecuted( assert, this )
	);

	// The submission is always intercepted because it does not come from the edit form.
	this.$form.find( '[name=some-input]' ).trigger( 'input' );
	this.$form.trigger( 'submit' );

	return Promise.all( [ delay(), result ] ).then(
		() => assertSubmissionDone( assert, this )
	);
} );

QUnit.test( 'should intercept edit form submissions if they come from wpSave', function ( assert ) {
	this.window.document.head.appendChild.callsFake( () => {
		assert.false( this.isLoadingIndicatorVisible(), 'should not show loading indicator prior to execute' );
		this.window.onHCaptchaSDKLoaded();
	} );
	this.window.hcaptcha.render.returns( 'some-captcha-id' );
	this.window.hcaptcha.execute.callsFake( async () => {
		assert.true( this.isLoadingIndicatorVisible(), 'loading indicator should be visible during execute' );
		return { response: 'some-token' };
	} );

	this.$form.attr( 'id', 'editform' );

	const result = useSecureEnclave( this.window ).then(
		() => assertHCaptchaWasExecuted( assert, this )
	);

	this.$form.find( '[name=some-input]' ).trigger( 'input' );
	this.$form.find( '#wpSave' ).trigger( 'click' );

	return result.then(
		() => assertSubmissionDone( assert, this )
	);
} );

QUnit.test( 'should not intercept edit form submissions not coming from wpSave', async function ( assert ) {
	this.window.document.head.appendChild.callsFake( () => {
		assert.false( this.isLoadingIndicatorVisible(), 'should not show loading indicator prior to execute' );
		this.window.onHCaptchaSDKLoaded();
	} );
	this.window.hcaptcha.render.returns( 'some-captcha-id' );
	this.window.hcaptcha.execute.callsFake( async () => {
		assert.true( this.isLoadingIndicatorVisible(), 'loading indicator should be visible during execute' );
		return { response: 'some-token' };
	} );

	this.$form.attr( 'id', 'editform' );

	let resolved = false;

	const result = useSecureEnclave( this.window ).then( () => {
		resolved = true;
	} );

	this.$form.find( '[name=some-input]' ).trigger( 'input' );
	this.$form.find( '#wpPreview' ).trigger( 'submit' );

	await delay();

	assert.false(
		resolved,
		'A captcha should not be shown when clicking on the review button'
	);
	assert.false(
		this.window.hcaptcha.execute.calledOnce,
		'should have not run hCaptcha when clicking the Review button'
	);

	// Ensure clicking Save after requesting a preview still triggers the captcha.
	//
	// Tests a scenario where the user first clicks a submit button not triggering a
	// captcha (i.e. "Show Preview" in the edit form), then immediately cancels loading
	// the new page (pressing Esc or using the browser's Stop button) and clicks on the
	// Save button instead (which should trigger a captcha).
	//
	// This can happen over slow connections without explicitly canceling the page load
	// if the Preview request goes slow enough to allow the user to click the Save button
	// before the response for the Preview request has been received.
	this.$form.find( '#wpSave' ).trigger( 'click' );

	await delay();

	assert.true(
		resolved,
		'A captcha should be shown when clicking on the save button'
	);
	assert.true(
		this.window.hcaptcha.execute.called,
		'should have run hCaptcha when clicking the Save button'
	);

	return result;
} );

QUnit.test( 'should intercept edit form save in older browsers when #wpSave was clicked', function ( assert ) {
	this.window.document.head.appendChild.callsFake( () => {
		assert.false( this.isLoadingIndicatorVisible(), 'should not show loading indicator prior to execute' );
		this.window.onHCaptchaSDKLoaded();
	} );
	this.window.hcaptcha.render.returns( 'some-captcha-id' );
	this.window.hcaptcha.execute.callsFake( async () => {
		assert.true( this.isLoadingIndicatorVisible(), 'loading indicator should be visible during execute' );
		return { response: 'some-token' };
	} );

	this.$form.attr( 'id', 'editform' );

	const result = useSecureEnclave( this.window ).then(
		() => assertHCaptchaWasExecuted( assert, this )
	);

	// Trigger input to initialise hCaptcha (and register the click handler)
	this.$form.find( '[name=some-input]' ).trigger( 'input' );

	// Click #wpSave to exercise the click.hCaptcha handler (sets isSaveChangesClick flag).
	// A one-time handler prevents the resulting form submission from using the modern
	// SubmitEvent.submitter path, so we can then simulate the older-browser path below.
	this.$form.one( 'click', '#wpSave', ( e ) => {
		e.preventDefault();
	} );
	this.$form.find( '#wpSave' ).trigger( 'click' );

	// Simulate form submission without SubmitEvent.submitter
	const event = $.Event( 'submit' );
	event.originalEvent = { preventDefault: function () {} };
	this.$form.trigger( event );

	return result.then(
		() => assertSubmissionDone( assert, this )
	);
} );

QUnit.test( 'should not intercept edit form submissions in older browsers when #wpSave was not clicked', async function ( assert ) {
	this.window.document.head.appendChild.callsFake( () => {
		this.window.onHCaptchaSDKLoaded();
	} );
	this.window.hcaptcha.render.returns( 'some-captcha-id' );

	this.$form.attr( 'id', 'editform' );

	let resolved = false;
	useSecureEnclave( this.window ).then( () => {
		resolved = true;
	} );

	// Pre-set the flag to verify executeWorkflow clears it at startup
	this.$form.data( 'isSaveChangesClick', true );
	this.$form.find( '[name=some-input]' ).trigger( 'input' );

	assert.strictEqual(
		this.$form.data( 'isSaveChangesClick' ),
		undefined,
		'executeWorkflow should clear isSaveChangesClick flag at startup'
	);

	// Simulate form submission without SubmitEvent.submitter and without a prior #wpSave click
	const event = $.Event( 'submit' );
	event.originalEvent = { preventDefault: function () {} };
	this.$form.trigger( event );

	await delay();

	assert.false(
		resolved,
		'captcha should not be shown when wpSave was not clicked'
	);
	assert.false(
		this.window.hcaptcha.execute.called,
		'should not run hCaptcha when wpSave was not clicked in older browsers'
	);
} );

QUnit.test( 'should measure hCaptcha load and execute timing for successful submission', function ( assert ) {
	mw.config.set( 'wgCanonicalSpecialPageName', 'CreateAccount' );

	this.measure
		.onFirstCall().returns( { duration: 1718 } )
		.onSecondCall().returns( { duration: 2314 } );

	this.window.document.head.appendChild.callsFake( () => {
		this.window.onHCaptchaSDKLoaded();
	} );
	this.window.hcaptcha.render.returns( 'some-captcha-id' );
	this.window.hcaptcha.execute.callsFake( async () => ( { response: 'some-token' } ) );

	const result = useSecureEnclave( this.window )
		.then( () => {
			assert.strictEqual( this.track.callCount, 9, 'should invoke mw.track() nine times' );
			assert.deepEqual(
				this.track.getCall( 0 ).args,
				[ 'specialCreateAccount.performanceTiming', 'hcaptcha-load', 1.718 ],
				'should emit event for load time'
			);
			assert.deepEqual(
				this.track.getCall( 1 ).args,
				[ 'stats.mediawiki_special_createaccount_hcaptcha_load_duration_seconds', 1718, { wiki: 'testwiki', outcome: 'success' } ],
				'should record account creation specific metric for load time'
			);
			assert.deepEqual(
				this.track.getCall( 2 ).args,
				[ 'stats.mediawiki_confirmedit_hcaptcha_load_duration_seconds', 1718, { wiki: 'testwiki', interfaceName: 'createaccount', outcome: 'success' } ],
				'should record metric for load time'
			);
			assert.deepEqual(
				this.track.getCall( 3 ).args,
				[ 'stats.mediawiki_confirmedit_hcaptcha_load_attempts_total', 1, { wiki: 'testwiki', interfaceName: 'createaccount', outcome: 'success' } ],
				'should record metric for load attempts'
			);
			assert.deepEqual(
				this.track.getCall( 4 ).args,
				[ 'stats.mediawiki_confirmedit_hcaptcha_execute_total', 1, { wiki: 'testwiki', interfaceName: 'createaccount' } ],
				'should record event for execute'
			);
			assert.deepEqual(
				this.track.getCall( 5 ).args,
				[ 'stats.mediawiki_confirmedit_hcaptcha_form_submit_total', 1, { wiki: 'testwiki', interfaceName: 'createaccount' } ],
				'should record event for form submission'
			);
			assert.deepEqual(
				this.track.getCall( 6 ).args,
				[ 'specialCreateAccount.performanceTiming', 'hcaptcha-execute', 2.314 ],
				'should emit event for execution time'
			);
			assert.deepEqual(
				this.track.getCall( 7 ).args,
				[ 'stats.mediawiki_special_createaccount_hcaptcha_execute_duration_seconds', 2314, { wiki: 'testwiki', outcome: 'success' } ],
				'should record account creation specific metric for execution time'
			);
			assert.deepEqual(
				this.track.getCall( 8 ).args,
				[ 'stats.mediawiki_confirmedit_hcaptcha_execute_duration_seconds', 2314, { wiki: 'testwiki', interfaceName: 'createaccount', outcome: 'success' } ],
				'should record metric for execution time'
			);
		} );

	this.$form.find( '[name=some-input]' ).trigger( 'input' );
	this.$form.trigger( 'submit' );

	return result;
} );

QUnit.test( 'should surface load errors as soon as possible', async function ( assert ) {
	mw.config.set( 'wgAction', 'edit' );

	this.window.document.head.appendChild.callsFake( ( script ) => {
		assert.false( this.isLoadingIndicatorVisible(), 'should not show loading indicator prior to execute' );
		script.onerror();
	} );

	this.measure.onFirstCall().returns( { duration: 1718 } );

	const hCaptchaResult = useSecureEnclave( this.window );

	this.$form.find( '[name=some-input]' ).trigger( 'input' );

	await hCaptchaResult;

	assert.notStrictEqual(
		this.$form.find( '.cdx-message' ).css( 'display' ),
		'none',
		'error message container should be visible'
	);
	assert.strictEqual(
		this.$form.find( '.cdx-message' ).text(),
		'(hcaptcha-generic-error)',
		'load error message should be set'
	);

	// One script_error_total per failed attempt, plus one load_duration and
	// one load_attempts sample emitted on the terminal outcome, plus the
	// hCaptchaRenderCallback 'error' event from the rejection handler.
	assert.strictEqual( this.track.callCount, 5, 'should invoke mw.track() five times' );
	assert.deepEqual(
		this.track.getCall( 0 ).args,
		[
			'stats.mediawiki_confirmedit_hcaptcha_script_error_total',
			1,
			{ wiki: 'testwiki', interfaceName: 'edit', phase: 'retrying' }
		],
		'should record metric for total errors after the first attempt with phase=retrying'
	);
	assert.deepEqual(
		this.track.getCall( 1 ).args,
		[
			'stats.mediawiki_confirmedit_hcaptcha_script_error_total',
			1,
			{ wiki: 'testwiki', interfaceName: 'edit', phase: 'terminal' }
		],
		'should record metric for total errors after the second attempt with phase=terminal'
	);
	assert.deepEqual(
		this.track.getCall( 2 ).args,
		[
			'stats.mediawiki_confirmedit_hcaptcha_load_duration_seconds',
			1718,
			{ wiki: 'testwiki', interfaceName: 'edit', outcome: 'failure' }
		],
		'should record metric for load time once on terminal failure with outcome=failure'
	);
	assert.deepEqual(
		this.track.getCall( 3 ).args,
		[
			'stats.mediawiki_confirmedit_hcaptcha_load_attempts_total',
			2,
			{ wiki: 'testwiki', interfaceName: 'edit', outcome: 'failure' }
		],
		'should record attempts=MAX_LOAD_ATTEMPTS with outcome=failure on terminal failure'
	);
	assert.deepEqual(
		this.track.getCall( 4 ).args,
		[
			'confirmEdit.hCaptchaRenderCallback',
			'error',
			'edit',
			'generic-error'
		],
		'should emit event for load failure'
	);
	assert.deepEqual(
		this.window.document.head.removeChild.callCount,
		1,
		'should remove previous script tags for the hCaptcha SDK between reties'
	);

	// One time per load attempt
	assert.strictEqual(
		this.logError.callCount,
		2,
		'should invoke mw.errorLogger.logError() twice'
	);

	const logFirstCallErrorArguments = this.logError.getCall( 0 ).args;
	const firstError = logFirstCallErrorArguments[ 0 ];
	assert.strictEqual(
		firstError.message,
		'Unable to load hCaptcha script (retrying)',
		'first attempt should produce a normalised retrying message'
	);
	assert.deepEqual(
		firstError.error_context,
		{
			attempt: '1/2',
			hostname: 'test.example',
			effectiveType: '4g',
			timeSinceNavigationMs: '1234',
			scriptSrc: 'https://example.com/hcaptcha.js?onload=onHCaptchaSDKLoaded'
		},
		'first attempt should attach diagnostic details via error_context'
	);
	assert.deepEqual(
		logFirstCallErrorArguments[ 1 ],
		'error.confirmedit',
		'should use correct channel for errors'
	);

	const logSecondCallErrorArguments = this.logError.getCall( 1 ).args;
	const secondError = logSecondCallErrorArguments[ 0 ];
	assert.strictEqual(
		secondError.message,
		'Unable to load hCaptcha script (terminal)',
		'second attempt should produce a normalised terminal message'
	);
	assert.strictEqual(
		secondError.error_context.attempt,
		'2/2',
		'second attempt should record attempt 2/2 in error_context'
	);
	assert.deepEqual(
		logSecondCallErrorArguments[ 1 ],
		'error.confirmedit',
		'should use correct channel for errors'
	);
} );

QUnit.test( 'should surface irrecoverable workflow execution errors as soon as possible', async function ( assert ) {
	// Explicitly set an unknown value here to test the unknown interface handling
	mw.config.set( 'wgAction', 'unknown' );

	this.window.document.head.appendChild.callsFake( () => {
		assert.false( this.isLoadingIndicatorVisible(), 'should not show loading indicator prior to execute' );
		this.window.onHCaptchaSDKLoaded();
	} );
	this.window.hcaptcha.render.returns( 'some-captcha-id' );
	this.window.hcaptcha.execute.callsFake( () => {
		assert.true( this.isLoadingIndicatorVisible(), 'loading indicator should be visible until hCaptcha finishes' );
		assert.true( this.areSubmitButtonsDisabled(), 'submit buttons should be disabled during execute' );
		return Promise.reject( 'generic-error' );
	} );

	this.measure
		.onFirstCall().returns( { duration: 1718 } )
		.onSecondCall().returns( { duration: 2314 } );

	const hCaptchaResult = useSecureEnclave( this.window );

	this.$form.find( '[name=some-input]' ).trigger( 'input' );
	this.$form.trigger( 'submit' );

	await hCaptchaResult;

	assert.false( this.isLoadingIndicatorVisible(), 'should hide loading indicator' );
	assert.false( this.areSubmitButtonsDisabled(), 'submit buttons should be re-enabled on error' );

	assert.notStrictEqual(
		this.$form.find( '.cdx-message' ).css( 'display' ),
		'none',
		'error message container should be visible'
	);
	assert.strictEqual(
		this.$form.find( '.cdx-message' ).text(),
		'(hcaptcha-generic-error)',
		'error message should be set'
	);

	assert.strictEqual( this.track.callCount, 5, 'should invoke mw.track() five times' );
	assert.deepEqual(
		this.track.getCall( 0 ).args,
		[ 'stats.mediawiki_confirmedit_hcaptcha_load_duration_seconds', 1718, { wiki: 'testwiki', interfaceName: 'unknown', outcome: 'success' } ],
		'should record metric for load time'
	);
	assert.deepEqual(
		this.track.getCall( 1 ).args,
		[ 'stats.mediawiki_confirmedit_hcaptcha_load_attempts_total', 1, { wiki: 'testwiki', interfaceName: 'unknown', outcome: 'success' } ],
		'should record metric for load attempts'
	);
	assert.deepEqual(
		this.track.getCall( 2 ).args,
		[ 'stats.mediawiki_confirmedit_hcaptcha_execute_total', 1, { wiki: 'testwiki', interfaceName: 'unknown' } ],
		'should emit event for execution'
	);
	assert.deepEqual(
		this.track.getCall( 3 ).args,
		[ 'stats.mediawiki_confirmedit_hcaptcha_execute_duration_seconds', 2314, { wiki: 'testwiki', interfaceName: 'unknown', outcome: 'failure' } ],
		'should record metric for load time with outcome=failure when execute rejects'
	);
	assert.deepEqual(
		this.track.getCall( 4 ).args,
		[ 'stats.mediawiki_confirmedit_hcaptcha_execute_workflow_error_total', 1, { wiki: 'testwiki', interfaceName: 'unknown', code: 'generic_error' } ],
		'should emit event for execution failure'
	);
} );

QUnit.test.each( 'should surface recoverable workflow execution errors on submit', {
	'challenge-closed': {
		error: 'challenge-closed',
		message: 'hcaptcha-challenge-closed'
	},
	'challenge-expired': {
		error: 'challenge-expired',
		message: 'hcaptcha-challenge-expired'
	},
	'internal-error': {
		error: 'internal-error',
		message: 'hcaptcha-internal-error'
	},
	'network-error': {
		error: 'network-error',
		message: 'hcaptcha-network-error'
	},
	'rate-limited': {
		error: 'rate-limited',
		message: 'hcaptcha-rate-limited'
	}
}, function ( assert, data ) {
	this.window.document.head.appendChild.callsFake( () => {
		assert.false( this.isLoadingIndicatorVisible(), 'should not show loading indicator prior to execute' );
		this.window.onHCaptchaSDKLoaded();
	} );

	this.window.hcaptcha.render.returns( 'some-captcha-id' );
	this.window.hcaptcha.execute.callsFake( () => {
		assert.true( this.isLoadingIndicatorVisible(), 'loading indicator should be visible during execute' );
		assert.true( this.areSubmitButtonsDisabled(), 'submit buttons should be disabled during execute' );
		return Promise.reject( data.error );
	} );

	useSecureEnclave( this.window );

	const formSubmitted = new Promise( ( resolve ) => {
		this.$form.one( 'submit', () => setTimeout( resolve ) );
	} );

	this.$form.find( '[name=some-input]' ).trigger( 'input' );
	this.$form.trigger( 'submit' );

	return formSubmitted.then( () => {
		assert.false( this.isLoadingIndicatorVisible(), 'should hide loading indicator' );
		assert.false( this.areSubmitButtonsDisabled(), 'submit buttons should be re-enabled on recoverable error' );

		assert.true( this.submit.notCalled, 'submit should have been prevented' );

		assert.notStrictEqual(
			this.$form.find( '.cdx-message' ).css( 'display' ),
			'none',
			'error message container should be visible'
		);
		assert.strictEqual(
			this.$form.find( '.cdx-message' ).text(),
			`(${ data.message })`,
			`error message should be set to (${ data.message })`
		);
	} );
} );

QUnit.test( 'should allow recovering from a recoverable error by starting a new workflow', function ( assert ) {
	this.window.document.head.appendChild.callsFake( () => {
		assert.false( this.isLoadingIndicatorVisible(), 'should not show loading indicator prior to execute' );
		this.window.onHCaptchaSDKLoaded();
	} );

	this.window.hcaptcha.render.returns( 'some-captcha-id' );
	this.window.hcaptcha.execute
		.onFirstCall().callsFake( () => {
			assert.true( this.areSubmitButtonsDisabled(), 'submit buttons should be disabled during first execute' );
			return Promise.reject( 'challenge-closed' );
		} )
		.onSecondCall().callsFake( () => {
			assert.true( this.areSubmitButtonsDisabled(), 'submit buttons should be disabled during second execute' );
			return Promise.resolve( { response: 'some-token' } );
		} );

	const result = useSecureEnclave( this.window )
		.then( () => {
			assert.false( this.isLoadingIndicatorVisible(), 'should hide loading indicator' );
			assert.false( this.areSubmitButtonsDisabled(), 'submit buttons should be re-enabled after success' );

			assert.true( this.window.hcaptcha.render.calledOnce, 'should render hCaptcha widget once' );
			assert.deepEqual(
				this.window.hcaptcha.render.firstCall.args[ 0 ],
				'h-captcha',
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
		} );

	this.$form.find( '[name=some-input]' ).trigger( 'input' );

	this.$form.one( 'submit', () => setTimeout( () => this.$form.trigger( 'submit' ) ) );

	this.$form.trigger( 'submit' );

	return Promise.all( [ delay(), result ] ).then(
		() => assertSubmissionDone( assert, this )
	);
} );

QUnit.test( 'should disable submit buttons during hCaptcha execution on edit page', function ( assert ) {
	mw.config.set( 'wgAction', 'edit' );
	this.window.document.head.appendChild.callsFake( () => {
		assert.false( this.isLoadingIndicatorVisible(), 'should not show loading indicator prior to execute' );
		this.window.onHCaptchaSDKLoaded();
	} );
	this.window.hcaptcha.render.returns( 'some-captcha-id' );
	this.window.hcaptcha.execute.callsFake( async () => {
		assert.true( this.isLoadingIndicatorVisible(), 'loading indicator should be visible during execute' );
		assert.true( this.areSubmitButtonsDisabled(), 'submit button should be disabled during execute' );
		return { response: 'some-token' };
	} );

	const result = useSecureEnclave( this.window )
		.then( () => {
			assert.false( this.isLoadingIndicatorVisible(), 'should hide loading indicator' );
			assert.false( this.areSubmitButtonsDisabled(), 'submit button should be re-enabled after success' );

			const infuseCall = window.OO.ui.infuse.firstCall;
			assert.strictEqual(
				infuseCall.args[ 0 ][ 0 ],
				document.getElementById( 'wpSaveWidget' ),
				'should call OO.ui.infuse with correct element'
			);

			assert.true( this.window.hcaptcha.render.calledOnce, 'should render hCaptcha widget once' );
			assert.true( this.window.hcaptcha.execute.calledOnce, 'should run hCaptcha once' );
			assert.true( this.submit.calledOnce, 'should submit form once hCaptcha token is available' );
			assert.strictEqual(
				this.$form.find( '#h-captcha-response' ).val(),
				'some-token',
				'should add hCaptcha response token to form'
			);
		} );

	this.$form.find( '[name=some-input]' ).trigger( 'input' );
	this.$form.trigger( 'submit' );

	return result;
} );

QUnit.test( 'should fire the confirmEdit.hCaptcha.executed hook when executeHCaptcha succeeds', async function ( assert ) {
	this.window.document.head.appendChild.callsFake( () => {
		assert.false( this.isLoadingIndicatorVisible(), 'should not show loading indicator prior to execute' );
		this.window.onHCaptchaSDKLoaded();
	} );
	this.window.hcaptcha.render.returns( 'some-captcha-id' );
	this.window.hcaptcha.execute.callsFake( async () => ( { response: 'some-token' } ) );

	const hook = mw.hook( 'confirmEdit.hCaptcha.executionSuccess' );
	const spy = this.sandbox.spy( hook, 'fire' );

	// The promise returned by useSecureEnclave() won't resolve
	// until the form is submitted.
	const result = useSecureEnclave( this.window );

	this.$form.find( '[name=some-input]' ).trigger( 'input' );
	this.$form.trigger( 'submit' );

	await result;

	assert.true( spy.calledOnce, 'Hook was fired once' );
	assert.deepEqual(
		spy.firstCall.args[ 0 ],
		'some-token',
		'Hook was fired with expected arguments'
	);

	// Clean up spy to avoid affecting later tests
	spy.restore();
} );

QUnit.test( 'edit form: falls back to native button selector when OO is absent', function ( assert ) {
	mw.config.set( 'wgAction', 'edit' );
	this.window.document.head.appendChild.callsFake( () => {
		this.window.onHCaptchaSDKLoaded();
	} );
	this.window.hcaptcha.render.returns( 'some-captcha-id' );

	// The Grade C fallback only disables buttons inside #wpSaveWidget — query that
	// directly rather than the suite-wide areSubmitButtonsDisabled helper (which
	// scans all form-level submits, including the test fixture's other buttons).
	const $saveButton = $( '<button type="submit" id="wpSaveButton">Save</button>' );
	this.$form.find( '#wpSaveWidget' ).append( $saveButton );

	this.window.hcaptcha.execute.callsFake( async () => {
		assert.true( $saveButton.prop( 'disabled' ), '#wpSaveWidget button should be disabled during execute' );
		return { response: 'some-token' };
	} );

	const savedOO = window.OO;
	window.OO = undefined;

	const result = useSecureEnclave( this.window )
		.then( () => {
			window.OO = savedOO;
			assert.true( window.OO.ui.infuse.notCalled, 'OO.ui.infuse should not have been called' );
			assert.false( $saveButton.prop( 'disabled' ), '#wpSaveWidget button should be re-enabled after success' );
		} );

	this.$form.find( '[name=some-input]' ).trigger( 'input' );
	this.$form.trigger( 'submit' );

	return result.catch( ( err ) => {
		window.OO = savedOO;
		throw err;
	} );
} );

QUnit.test( 'should submit the form immediately when wgHCaptchaTriggerFormSubmission is set', async function ( assert ) {
	this.window.document.head.appendChild.callsFake( () => {
		assert.false( this.isLoadingIndicatorVisible(), 'should not show loading indicator prior to execute' );
		this.window.onHCaptchaSDKLoaded();
	} );

	this.window.hcaptcha.render.returns( 'some-captcha-id' );
	this.window.hcaptcha.execute.callsFake( async () => ( { response: 'some-token' } ) );

	// (T411963) HCaptcha::setForceShowCaptcha() is called when the editor page is
	// reloaded because an AbuseFilter triggered an hCaptcha workflow, which in
	// turn makes the backend to set this Javascript variable on page reload.
	mw.config.set( 'wgHCaptchaTriggerFormSubmission', true );

	let isSubmitted = false;
	this.$form.on( 'submit', () => {
		isSubmitted = true;
	} );

	// The promise returned by useSecureEnclave() won't resolve
	// until the form is submitted.
	let isResolved = false;
	useSecureEnclave( this.window ).then( () => {
		isResolved = true;
	} );

	assert.false(
		isResolved,
		'useSecureEnclave should not resolve without a form submission'
	);
	assert.false(
		isSubmitted,
		'useSecureEnclave should not submit the form immediately'
	);

	// The form submission waits for the hCaptcha ID promise to resolve before
	// triggering the submission, so we use polling here.
	let iterations = 0;

	// isSubmitted is modified by a form submission event handler.
	// eslint-disable-next-line no-unmodified-loop-condition
	while ( !isSubmitted ) {
		await delay( 10 );
		iterations++;

		if ( iterations === 100 ) {
			assert.true(
				false,
				'Setting the global variable should trigger a form submission'
			);
		}
	}

	assert.false(
		isResolved,
		'Setting the global variable should make useSecureEnclave to remain unresolved'
	);
} );

QUnit.test.each(
	'should re-inject the clicked submit button name=value as a hidden input',
	{
		'modern browser (SubmitEvent.submitter present)': { useSubmitterProp: true },
		'older browser (no SubmitEvent.submitter, click captured first)': { useSubmitterProp: false }
	},
	async function ( assert, opts ) {
		this.window.document.head.appendChild.callsFake( () => {
			this.window.onHCaptchaSDKLoaded();
		} );
		this.window.hcaptcha.render.returns( 'some-captcha-id' );
		this.window.hcaptcha.execute.callsFake( async () => ( { response: 'some-token' } ) );

		// The default #wpSave button in the fixture has no name attribute,
		// so add a named submit button to exercise the capture path.
		const namedButton = $( '<input type="submit" name="wpSave" value="Save changes">' ).appendTo( this.$form )[ 0 ];

		const enclavePromise = useSecureEnclave( this.window );

		// Prime hCaptcha via an input event so submit.hCaptcha is registered
		// before we trigger the submit that should run the workflow.
		this.$form.find( '[name=some-input]' ).trigger( 'input' );
		if ( opts.useSubmitterProp ) {
			this.$form.trigger( $.Event( 'submit', { submitter: namedButton } ) );
		} else {
			$( namedButton ).trigger( 'click' );
			this.$form.trigger( 'submit' );
		}

		await enclavePromise;
		await delay();

		const $hidden = this.$form.find( 'input.mw-confirmedit-hcaptcha-submitter' );
		assert.strictEqual( $hidden.length, 1, 'should append exactly one hidden submitter input' );
		assert.strictEqual( $hidden.attr( 'name' ), 'wpSave', 'hidden input should have submitter name' );
		assert.strictEqual( $hidden.val(), 'Save changes', 'hidden input should have submitter value' );
		assert.strictEqual( this.submit.callCount, 1, 'form.submit() should have been called once' );
	}
);

QUnit.test( 'should load hCaptcha when the MCR form Save button is focused', async function ( assert ) {
	this.window.document.head.appendChild.callsFake( () => {
		this.window.onHCaptchaSDKLoaded();
	} );
	this.window.hcaptcha.render.returns( 'mcr-captcha-id' );

	// The mcrundo/mcrrestore Save control is a <button>, unlike the edit form's
	// <input type=submit>. The loader must watch buttons too, otherwise focusing
	// (and so clicking) Save would not initialise hCaptcha until a second click.
	const saveButton = $( '<button type="submit" name="wpSave">' ).appendTo( this.$form )[ 0 ];

	useSecureEnclave( this.window );

	$( saveButton ).trigger( 'focus' );
	await delay();

	assert.true(
		this.window.document.head.appendChild.calledOnce,
		'should load the hCaptcha SDK when a submit button is focused'
	);
	assert.true(
		this.window.hcaptcha.render.calledOnce,
		'should render the hCaptcha widget when a submit button is focused'
	);
} );

QUnit.test.each(
	'should only require a captcha for the Save button on MCR edit actions',
	{ mcrundo: 'mcrundo', mcrrestore: 'mcrrestore' },
	async function ( assert, action ) {
		this.window.document.head.appendChild.callsFake( () => {
			this.window.onHCaptchaSDKLoaded();
		} );
		this.window.hcaptcha.render.returns( 'mcr-captcha-id' );
		this.window.hcaptcha.execute.callsFake( async () => ( { response: 'some-token' } ) );

		mw.config.set( 'wgAction', action );

		// The mcrundo/mcrrestore form identifies its buttons by name with no id,
		// so drop the fixture's id-bearing #wpSave to genuinely exercise the
		// name-based matching.
		this.$form.find( '#wpSave' ).remove();

		const previewButton = $( '<button type="submit" name="wpPreview">' ).appendTo( this.$form )[ 0 ];
		const diffButton = $( '<button type="submit" name="wpDiff">' ).appendTo( this.$form )[ 0 ];
		const saveButton = $( '<button type="submit" name="wpSave">' ).appendTo( this.$form )[ 0 ];

		useSecureEnclave( this.window );

		// Load hCaptcha via a first interaction before exercising submit handling.
		this.$form.find( '[name=some-input]' ).trigger( 'input' );
		await delay();

		// Show preview should submit normally without engaging hCaptcha.
		this.$form.trigger( $.Event( 'submit', { submitter: previewButton } ) );
		await delay();
		assert.true(
			this.window.hcaptcha.execute.notCalled,
			'a Preview submit should not trigger an hCaptcha challenge'
		);

		// Show changes should submit normally without engaging hCaptcha.
		this.$form.trigger( $.Event( 'submit', { submitter: diffButton } ) );
		await delay();
		assert.true(
			this.window.hcaptcha.execute.notCalled,
			'a Show changes (diff) submit should not trigger an hCaptcha challenge'
		);

		// Save should engage hCaptcha.
		this.$form.trigger( $.Event( 'submit', { submitter: saveButton } ) );
		await delay();
		assert.true(
			this.window.hcaptcha.execute.called,
			'a Save submit should trigger an hCaptcha challenge'
		);
	}
);
