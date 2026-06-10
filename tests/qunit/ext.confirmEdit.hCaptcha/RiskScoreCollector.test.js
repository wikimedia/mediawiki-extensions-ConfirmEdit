const RiskScoreCollector = require( 'ext.confirmEdit.hCaptcha/ext.confirmEdit.hCaptcha/RiskScoreCollector.js' );

QUnit.module(
	'ext.confirmEdit.hCaptcha.RiskScoreCollector',
	QUnit.newMwEnvironment( {
		beforeEach() {
			this.utils = require( 'ext.confirmEdit.hCaptcha/ext.confirmEdit.hCaptcha/utils.js' );
			this.track = this.sandbox.stub( mw, 'track' );
			this.loadAndRenderHCaptcha = this.sandbox.stub(
				this.utils,
				'loadAndRenderHCaptcha'
			);
			this.executeHCaptcha = this.sandbox.stub(
				this.utils,
				'executeHCaptcha'
			);
			this.container = {
				setAttribute: this.sandbox.stub()
			};
			this.window = {
				document: {
					createElement: this.sandbox.stub().returns( this.container ),
					body: {
						appendChild: this.sandbox.stub()
					}
				}
			};

			this.restPost = this.sandbox.stub().returns( Promise.resolve() );
			this.Rest = this.sandbox.stub( mw, 'Rest' ).returns( {
				post: this.restPost
			} );
			this.logError = this.sandbox.stub( mw.errorLogger, 'logError' );
			this.getPageviewToken = this.sandbox
				.stub( mw.user, 'getPageviewToken' )
				.returns( 'test-pageview-token' );

			mw.config.set( 'wgPageName', 'Test_page' );

			this.loadAndRenderHCaptcha.returns( Promise.resolve( 'test-captcha-id' ) );
			this.executeHCaptcha.returns( Promise.resolve( 'test-response-token' ) );
		},
		afterEach() {
			RiskScoreCollector.reset();
		}
	} )
);

QUnit.test(
	'collectRiskScoreForBlockedUser does nothing when siteKey is missing',
	async function ( assert ) {
		await RiskScoreCollector.collectRiskScoreForBlockedUser( this.window, null );

		assert.true( this.loadAndRenderHCaptcha.notCalled, 'loadAndRenderHCaptcha should not be called' );
		assert.true( this.window.document.body.appendChild.notCalled, 'No container should be appended to the body' );
		assert.true( this.restPost.notCalled, 'api.post should not be called' );
	}
);

QUnit.test(
	'collectRiskScoreForBlockedUser renders, executes and posts the risk score token',
	async function ( assert ) {
		await RiskScoreCollector.collectRiskScoreForBlockedUser( this.window, 'sk' );

		assert.true( this.window.document.createElement.calledWith( 'div' ), 'Should create a div container' );
		assert.true( this.container.setAttribute.calledWith( 'data-sitekey', 'sk' ), 'Container should carry the sitekey' );
		assert.true( this.window.document.body.appendChild.calledWith( this.container ), 'Container should be appended' );

		assert.true( this.loadAndRenderHCaptcha.calledOnce, 'loadAndRenderHCaptcha should be called once' );
		assert.true(
			this.loadAndRenderHCaptcha.calledWith( this.window, 'blocked-ip-risk-score', this.container ),
			'loadAndRenderHCaptcha should receive the window, interface name and container'
		);

		assert.true( this.executeHCaptcha.calledOnce, 'executeHCaptcha should be called once' );
		assert.true(
			this.executeHCaptcha.calledWith( this.window, 'test-captcha-id', 'blocked-ip-risk-score' ),
			'executeHCaptcha should use the captchaId from the render call'
		);

		assert.true( this.restPost.calledOnce, 'api.post should be called once' );
		assert.deepEqual(
			this.restPost.args[ 0 ],
			[
				'/confirmedit/v0/hcaptcha/blocktoken',
				{
					riskScoreToken: 'test-response-token',
					page: 'Test_page',
					pageViewId: 'test-pageview-token'
				}
			],
			'api.post should be called with the exact endpoint and body, without block ID keys'
		);
	}
);

QUnit.test(
	'collectRiskScoreForBlockedUser submits only once per page view',
	async function ( assert ) {
		await RiskScoreCollector.collectRiskScoreForBlockedUser( this.window, 'sk' );
		await RiskScoreCollector.collectRiskScoreForBlockedUser( this.window, 'sk' );

		assert.true( this.loadAndRenderHCaptcha.calledOnce, 'loadAndRenderHCaptcha should only be called once' );
		assert.true( this.restPost.calledOnce, 'api.post should only be called once' );
	}
);

QUnit.test(
	'collectRiskScoreForBlockedUser does not start a second request while one is in flight',
	async function ( assert ) {
		let resolveRender;
		this.loadAndRenderHCaptcha.returns( new Promise( ( resolve ) => {
			resolveRender = resolve;
		} ) );

		const firstCall = RiskScoreCollector.collectRiskScoreForBlockedUser( this.window, 'sk' );
		const secondCall = RiskScoreCollector.collectRiskScoreForBlockedUser( this.window, 'sk' );

		assert.strictEqual( secondCall, firstCall, 'The concurrent call should return the same pending promise' );
		assert.true( this.loadAndRenderHCaptcha.calledOnce, 'loadAndRenderHCaptcha should only be called once' );

		resolveRender( 'test-captcha-id' );
		await firstCall;

		assert.true( this.restPost.calledOnce, 'api.post should only be called once' );
	}
);

QUnit.test(
	'collectRiskScoreForBlockedUser logs an error and allows a retry when the POST fails',
	async function ( assert ) {
		const details = { exception: 'Not Found', textStatus: 'error' };
		const deferred = $.Deferred();
		deferred.reject( 'http', details );
		this.restPost.returns( deferred.promise() );

		await RiskScoreCollector.collectRiskScoreForBlockedUser( this.window, 'sk' );

		assert.true( this.track.notCalled, 'mw.track should not be called when the POST fails' );
		assert.true( this.logError.calledOnce, 'mw.errorLogger.logError should be called once' );

		const [ loggedError, channel ] = this.logError.args[ 0 ];
		assert.true( loggedError instanceof Error, 'logError should receive an Error instance' );
		assert.strictEqual( loggedError.message, 'Error with type {type} posting block token', 'The error message should be the expected literal string' );
		assert.deepEqual( loggedError.error_context, { details: details, type: 'http' }, 'The error should carry details and type as error_context' );
		assert.strictEqual( channel, 'error.confirmedit', 'logError should be called with the error channel' );

		this.restPost.returns( Promise.resolve() );
		await RiskScoreCollector.collectRiskScoreForBlockedUser( this.window, 'sk' );

		assert.true( this.loadAndRenderHCaptcha.calledTwice, 'A failed submission should not be marked as submitted, so a retry renders again' );
		assert.true( this.restPost.calledTwice, 'A failed submission should allow a second POST on retry' );
	}
);

QUnit.test(
	'collectRiskScoreForBlockedUser tracks an error when rendering or execution fails',
	async function ( assert ) {
		this.loadAndRenderHCaptcha.returns( Promise.reject( 'render-error' ) );

		await RiskScoreCollector.collectRiskScoreForBlockedUser( this.window, 'sk' );

		assert.true( this.restPost.notCalled, 'api.post should not be called when rendering fails' );
		assert.true(
			this.track.calledWith( 'confirmEdit.hCaptchaRenderCallback', 'error', 'blocked-ip-risk-score', 'render-error' ),
			'mw.track should be called with the error code from the failed render'
		);
	}
);

QUnit.test(
	'reset clears state so a fresh collection runs again',
	async function ( assert ) {
		await RiskScoreCollector.collectRiskScoreForBlockedUser( this.window, 'sk' );
		assert.true( this.restPost.calledOnce, 'api.post should be called once before reset' );

		RiskScoreCollector.reset();

		await RiskScoreCollector.collectRiskScoreForBlockedUser( this.window, 'sk' );
		assert.true( this.restPost.calledTwice, 'api.post should be called again after reset' );
	}
);
