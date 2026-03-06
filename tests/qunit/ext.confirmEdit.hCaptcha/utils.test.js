QUnit.module( 'ext.confirmEdit.hCaptcha.utils', QUnit.newMwEnvironment( {
	beforeEach() {
		mw.config.set( 'wgDBname', 'testwiki' );

		this.track = this.sandbox.stub( mw, 'track' );
		this.logError = this.sandbox.stub( mw.errorLogger, 'logError' );

		// (T411576) Mock for testing that instrumentation code measuring time spent on
		// hCaptcha execution falls back to performance.getEntriesByName() if
		// performance.measure()  does not return the PerformanceMeasure directly.
		this.getEntriesByName = this.sandbox.stub( performance, 'getEntriesByName' );

		// Sinon fake timers as of v21 only return a static fake value from performance.measure(),
		// so use a regular stub instead.
		this.measure = this.sandbox.stub( performance, 'measure' );
		this.measure.returns( { duration: 0 } );

		// (T411576) Mock for testing that instrumentation code measuring time spent on
		// hCaptcha execution falls back to mw.now() if using performance.measure() is
		// not possible.
		this.now = this.sandbox.stub( mw, 'now' );

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
					appendChild: this.sandbox.stub()
				},
				querySelectorAll: this.sandbox.stub().returns( [] )
			},
			performance: {
				measure: this.measure,
				getEntriesByName: this.getEntriesByName
			}
		};

		// Subject under test
		this.utils = require( 'ext.confirmEdit.hCaptcha/ext.confirmEdit.hCaptcha/utils.js' );
	},

	afterEach() {
		this.getEntriesByName.restore();
		this.logError.restore();
		this.measure.restore();
		this.now.restore();
		this.track.restore();

		// The result of require() is cached, so this is needed in order to drop
		// references to widgets added to the page by previous tests.
		this.utils.reset();
	}
} ) );

QUnit.test( 'should handle exception being thrown by hcaptcha.execute', async function ( assert ) {
	this.window.hcaptcha.execute.throws( new Error( 'generic-failure' ) );

	this.measure.onFirstCall().returns( { duration: 2314 } );
	this.measure.onSecondCall().returns( { duration: 1234 } );

	return this.utils.executeHCaptcha( this.window, 'captcha-id', 'testinterface' )
		.then( () => {
			// False positive
			// eslint-disable-next-line no-jquery/no-done-fail
			assert.fail( 'Did not expect promise to fulfill' );
		} )
		.catch( ( error ) => {
			assert.strictEqual(
				error,
				'generic-failure',
				'should return error to caller'
			);

			assert.strictEqual( this.track.callCount, 3, 'should invoke mw.track() three times' );
			assert.deepEqual(
				this.track.getCall( 0 ).args,
				[ 'stats.mediawiki_confirmedit_hcaptcha_execute_total', 1, { wiki: 'testwiki', interfaceName: 'testinterface' } ],
				'should emit event for execution'
			);
			assert.deepEqual(
				this.track.getCall( 1 ).args,
				[ 'stats.mediawiki_confirmedit_hcaptcha_execute_workflow_error_total', 1, { wiki: 'testwiki', interfaceName: 'testinterface', code: 'generic_failure' } ],
				'should emit event for execution'
			);
			assert.deepEqual(
				this.track.getCall( 2 ).args,
				[ 'stats.mediawiki_confirmedit_hcaptcha_execute_duration_seconds', 2314, { wiki: 'testwiki', interfaceName: 'testinterface' } ],
				'should record metric for load time'
			);

			assert.strictEqual( this.logError.callCount, 1, 'should invoke mw.errorLogger.logError() once' );
			const logErrorArguments = this.logError.getCall( 0 ).args;
			assert.deepEqual(
				logErrorArguments[ 0 ].message,
				'generic-failure',
				'should use correct channel for errors'
			);
			assert.deepEqual(
				logErrorArguments[ 1 ],
				'error.confirmedit',
				'should use correct channel for errors'
			);
		} );
} );

QUnit.test( 'loadHCaptcha should return early if previous hCaptcha SDK load succeeded', async function ( assert ) {
	this.window.document.head.appendChild.callsFake( async () => {
		this.window.onHCaptchaSDKLoaded();
	} );

	const $qunitFixture = $( '#qunit-fixture' );

	const script = document.createElement( 'script' );
	script.className = 'mw-confirmedit-hcaptcha-script mw-confirmedit-hcaptcha-script-loading-finished';
	$qunitFixture.append( script );

	this.window.document.querySelectorAll.returns( [ script ] );

	return this.utils.loadHCaptcha( this.window, 'testinterface' )
		.then( () => {
			assert.true( this.window.document.head.appendChild.notCalled, 'should not load hCaptcha SDK' );
			assert.true( this.track.notCalled, 'should not emit hCaptcha performance events' );
		} )
		.catch( () => {
			// False positive
			// eslint-disable-next-line no-jquery/no-done-fail
			assert.fail( 'Did not expect promise to reject' );
		} );
} );

QUnit.test( 'loadHCaptcha should load hCaptcha SDK if previous attempt failed', async function ( assert ) {
	this.window.document.head.appendChild.callsFake( async () => {
		this.window.onHCaptchaSDKLoaded();
	} );

	const $qunitFixture = $( '#qunit-fixture' );

	const script = document.createElement( 'script' );
	script.className = 'mw-confirmedit-hcaptcha-script mw-confirmedit-hcaptcha-script-loading-failed';
	$qunitFixture.append( script );

	assert.true( this.window.document.head.appendChild.notCalled, 'should not have loaded hCaptcha SDK until call' );

	return this.utils.loadHCaptcha( this.window, 'testinterface' )
		.then( () => {
			assert.true( this.window.document.head.appendChild.calledOnce, 'should load hCaptcha SDK' );
		} )
		.catch( () => {
			// False positive
			// eslint-disable-next-line no-jquery/no-done-fail
			assert.fail( 'Did not expect promise to reject' );
		} );
} );

QUnit.test( 'getDuration falls back to getEntriesByName() if measure() does not return a value', async function ( assert ) {
	this.window.document.head.appendChild.callsFake( async () => {
		this.window.onHCaptchaSDKLoaded();
	} );

	// (T411576) Fake the behavior of older browsers which don't return a
	// PerformanceMeasure object when calling performance.measure().
	this.measure.onFirstCall().returns( undefined );

	this.getEntriesByName.returns( [
		{ duration: 123 }
	] );

	// loadHCaptcha() sets an onHCaptchaSDKLoaded listener that calls
	// trackPerformanceTiming(). In turn, that calls getDuration(), which has a
	// fallback to getEntriesByName() if performance.measure() returns undefined.
	// Most browsers added support for getEntriesByName() in 2015 or earlier,
	// while returning a PerformanceMeasure object started around 2020.
	return this.utils.loadHCaptcha( this.window, 'testinterface' ).then( () => {
		assert.strictEqual(
			this.measure.callCount,
			1,
			'should have tried to use performance.measure first'
		);
		assert.strictEqual(
			this.getEntriesByName.callCount,
			1,
			'should fall back to getEntriesByName'
		);
		// Note getPerformanceStartMark() always calls mw.now() at least once in
		// order to build the timestamp associated with the startMark (the value
		// is only used if other measuring methods fail).
		assert.strictEqual(
			this.now.callCount,
			1,
			'should not fall back to mw.now() if getEntriesByName is available'
		);
	} );
} );

QUnit.test( 'getDuration falls back to mw.now() as a last resort', async function ( assert ) {
	this.window.document.head.appendChild.callsFake( async () => {
		this.window.onHCaptchaSDKLoaded();
	} );

	// (T411576) Fake the behavior of older browsers which don't return a
	// PerformanceMeasure object when calling performance.measure(), and make
	// getEntriesByName() to return empty data, which makes the fallback to
	// mw.now() to be triggered.
	this.measure.onFirstCall().returns( undefined );
	this.getEntriesByName.onFirstCall().returns( [] );

	this.now.returns( 987 );

	// loadHCaptcha() sets an onHCaptchaSDKLoaded listener that calls
	// trackPerformanceTiming(). In turn, that calls getDuration(), which has a
	// fallback to getEntriesByName() if performance.measure() returns undefined.
	// Most browsers added support for getEntriesByName() in 2015 or earlier,
	// while returning a PerformanceMeasure object started around 2020.
	return this.utils.loadHCaptcha( this.window, 'testinterface' ).then( () => {
		assert.strictEqual(
			this.measure.callCount,
			1,
			'should have tried to use performance.measure first'
		);
		assert.strictEqual(
			this.getEntriesByName.callCount,
			1,
			'should fall back to getEntriesByName first'
		);
		// Note getPerformanceStartMark() always calls mw.now() at least once in
		// order to build the timestamp associated with the startMark (the value
		// is only used if other measuring methods fail). The second call is
		// used in order to actually measure the duration based on that value.
		assert.strictEqual(
			this.now.callCount,
			2,
			'should fall back to mw.now() last'
		);
	} );
} );

QUnit.test( 'getRecoverableErrors returns the expected error codes', async function ( assert ) {
	const errors = this.utils.getRecoverableErrors();
	const expected = [
		'challenge-closed',
		'challenge-expired',
		'internal-error',
		'network-error',
		'rate-limited'
	];
	assert.deepEqual( errors.slice().sort(), expected.sort() );
} );
