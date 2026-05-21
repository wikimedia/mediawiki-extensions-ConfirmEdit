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

			this.loadAndRenderHCaptcha.returns( Promise.resolve() );
			this.executeHCaptcha.returns( Promise.resolve() );
		},
		afterEach() {
			this.track.restore();
			this.loadAndRenderHCaptcha.restore();
			this.executeHCaptcha.restore();
			this.Rest.restore();
			this.logError.restore();
			this.getPageviewToken.restore();

			this.utils.reset();
			RiskScoreCollector.reset();
		}
	} )
);

QUnit.test.each(
	'collectRiskScoreForBlockedUser returns early when required params are missing',
	{
		'empty localBlockIds and globalBlockIds': {
			localBlockIds: [],
			globalBlockIds: [],
			siteKey: 'test-site-key'
		},
		'missing siteKey': {
			localBlockIds: [ 123 ],
			globalBlockIds: [],
			siteKey: null
		}
	},
	function ( assert, ipBlockRiskScore ) {
		RiskScoreCollector.collectRiskScoreForBlockedUser( this.window, ipBlockRiskScore );

		assert.true(
			this.loadAndRenderHCaptcha.notCalled,
			'loadAndRenderHCaptcha should not be called'
		);
		assert.true(
			this.window.document.body.appendChild.notCalled,
			'No container should be appended to the document body'
		);
	}
);

QUnit.test(
	'collectRiskScoreForBlockedUser creates a div container and appends it to the document body',
	async function ( assert ) {
		await RiskScoreCollector.collectRiskScoreForBlockedUser(
			this.window,
			{
				localBlockIds: [ 123 ],
				globalBlockIds: [],
				siteKey: 'test-site-key'
			}
		);

		assert.true(
			this.window.document.createElement.calledWith( 'div' ),
			'Should create a div element for the hCaptcha container'
		);
		assert.true(
			this.window.document.body.appendChild.calledWith( this.container ),
			'Container should be appended to the document body'
		);
	}
);

QUnit.test(
	'collectRiskScoreForBlockedUser uses the correct container element and sitekey to load hCaptcha',
	async function ( assert ) {
		await RiskScoreCollector.collectRiskScoreForBlockedUser(
			this.window,
			{
				localBlockIds: [ 123 ],
				globalBlockIds: [],
				siteKey: 'test-site-key'
			}
		);

		assert.true(
			this.container.setAttribute.calledWith(
				'data-sitekey',
				'test-site-key'
			),
			'loadAndRenderHCaptcha should receive the container and sitekey'
		);
		assert.true(
			this.loadAndRenderHCaptcha.calledOnce,
			'loadAndRenderHCaptcha should be called once'
		);
		assert.true(
			this.loadAndRenderHCaptcha.calledWith(
				this.window,
				'blocked-ip-risk-score',
				this.container
			),
			'loadAndRenderHCaptcha should receive the container'
		);
	}
);

QUnit.test(
	'collectRiskScoreForBlockedUser calls executeHCaptcha using the captchaId from the render call',
	async function ( assert ) {
		this.loadAndRenderHCaptcha.returns(
			Promise.resolve( 'test-captcha-id' )
		);

		await RiskScoreCollector.collectRiskScoreForBlockedUser(
			this.window,
			{
				localBlockIds: [ 123 ],
				globalBlockIds: [],
				siteKey: 'test-site-key'
			}
		);

		assert.true(
			this.executeHCaptcha.calledOnce,
			'executeHCaptcha should be called once'
		);
		assert.true(
			this.executeHCaptcha.calledWith(
				this.window,
				'test-captcha-id',
				'blocked-ip-risk-score'
			),
			'The call to executeHCaptcha should use the correct captchaId'
		);
	}
);

QUnit.test(
	'collectRiskScoreForBlockedUser tracks an error when loadAndRenderHCaptcha rejects',
	async function ( assert ) {
		this.loadAndRenderHCaptcha.returns( Promise.reject( 'generic-error' ) );

		await RiskScoreCollector.collectRiskScoreForBlockedUser(
			this.window,
			{
				localBlockIds: [ 123 ],
				globalBlockIds: [],
				siteKey: 'test-site-key'
			}
		);

		assert.true(
			this.track.calledWith(
				'confirmEdit.hCaptchaRenderCallback',
				'error',
				'blocked-ip-risk-score',
				'generic-error'
			),
			'mw.track should be called with the error code from loadAndRenderHCaptcha'
		);
	}
);

QUnit.test(
	'collectRiskScoreForBlockedUser posts the token and block IDs to the REST endpoint',
	async function ( assert ) {
		this.loadAndRenderHCaptcha.returns(
			Promise.resolve( 'test-captcha-id' )
		);
		this.executeHCaptcha.returns(
			Promise.resolve( 'test-response-token' )
		);

		await RiskScoreCollector.collectRiskScoreForBlockedUser(
			this.window,
			{
				localBlockIds: [ 123, 456 ],
				globalBlockIds: [ 789 ],
				siteKey: 'test-site-key'
			}
		);

		assert.true(
			this.restPost.calledOnce,
			'api.post should be called once'
		);
		assert.deepEqual(
			this.restPost.args[ 0 ],
			[
				'/confirmedit/v0/hcaptcha/blocktoken',
				{
					riskScoreToken: 'test-response-token',
					localBlockIds: [ 123, 456 ],
					globalBlockIds: [ 789 ],
					pageViewId: 'test-pageview-token'
				}
			],
			'api.post should be called with the correct endpoint and body'
		);
	}
);

QUnit.test(
	'collectRiskScoreForBlockedUser logs an error when the REST POST fails',
	async function ( assert ) {
		const details = { exception: 'Not Found', textStatus: 'error' };
		const deferred = $.Deferred();
		deferred.reject( 'http', details );
		this.restPost.returns( deferred.promise() );

		await RiskScoreCollector.collectRiskScoreForBlockedUser(
			this.window,
			{
				localBlockIds: [ 123 ],
				globalBlockIds: [],
				siteKey: 'test-site-key'
			}
		);

		assert.true(
			this.track.notCalled,
			'mw.track should not be called when the REST POST fails'
		);
		assert.true(
			this.logError.calledOnce,
			'mw.errorLogger.logError should be called when the REST POST fails'
		);

		const [ loggedError, channel ] = this.logError.args[ 0 ];
		assert.true(
			loggedError instanceof Error,
			'mw.errorLogger.logError should be called with an Error instance'
		);
		assert.strictEqual(
			loggedError.message,
			'Error with type {type} posting block token',
			'The error message should be the expected literal string'
		);
		assert.deepEqual(
			loggedError.error_context,
			{ details: details, type: 'http' },
			'The error should carry the response details and type as error_context'
		);
		assert.strictEqual(
			channel,
			'error.confirmedit',
			'mw.errorLogger.logError should be called with the error channel'
		);
	}
);

QUnit.test(
	'collectRiskScoreForBlockedUser skips blocks already sent in a previous call',
	async function ( assert ) {
		const config = {
			localBlockIds: [ 123 ],
			globalBlockIds: [ 789 ],
			siteKey: 'test-site-key'
		};

		await RiskScoreCollector.collectRiskScoreForBlockedUser( this.window, config );
		await RiskScoreCollector.collectRiskScoreForBlockedUser( this.window, config );

		assert.true(
			this.loadAndRenderHCaptcha.calledOnce,
			'loadAndRenderHCaptcha should only be called once when the same blocks are sent twice'
		);
		assert.true(
			this.restPost.calledOnce,
			'api.post should only be called once when the same blocks are sent twice'
		);
	}
);

QUnit.test(
	'collectRiskScoreForBlockedUser only posts block IDs not already submitted',
	async function ( assert ) {
		this.executeHCaptcha.returns( Promise.resolve( 'test-response-token' ) );

		await RiskScoreCollector.collectRiskScoreForBlockedUser(
			this.window,
			{
				localBlockIds: [ 123, 456 ],
				globalBlockIds: [],
				siteKey: 'test-site-key'
			}
		);

		await RiskScoreCollector.collectRiskScoreForBlockedUser(
			this.window,
			{
				localBlockIds: [ 456, 789 ],
				globalBlockIds: [],
				siteKey: 'test-site-key'
			}
		);

		assert.deepEqual(
			this.restPost.args[ 1 ],
			[
				'/confirmedit/v0/hcaptcha/blocktoken',
				{
					riskScoreToken: 'test-response-token',
					localBlockIds: [ 789 ],
					globalBlockIds: [],
					pageViewId: 'test-pageview-token'
				}
			],
			'The second POST should only include block IDs not already submitted'
		);
	}
);

QUnit.test(
	'collectRiskScoreForBlockedUser does not skip blocks when the POST fails',
	async function ( assert ) {
		this.restPost.returns( Promise.reject( 'post-error' ) );

		const config = {
			localBlockIds: [ 123 ],
			globalBlockIds: [],
			siteKey: 'test-site-key'
		};

		await RiskScoreCollector.collectRiskScoreForBlockedUser( this.window, config );

		this.restPost.returns( Promise.resolve() );
		await RiskScoreCollector.collectRiskScoreForBlockedUser( this.window, config );

		assert.true(
			this.loadAndRenderHCaptcha.calledTwice,
			'loadAndRenderHCaptcha should be called again when the POST failed the first time'
		);
		assert.true(
			this.restPost.calledTwice,
			'api.post should be called again when the POST failed the first time'
		);
	}
);

QUnit.test(
	'collectRiskScoreForBlockedUser does not start a new request for IDs already in progress',
	async function ( assert ) {
		let resolveFirstRequest;
		this.loadAndRenderHCaptcha.returns(
			new Promise( ( resolve ) => {
				resolveFirstRequest = resolve;
			} )
		);

		const config = {
			localBlockIds: [ 123 ],
			globalBlockIds: [],
			siteKey: 'test-site-key'
		};

		// Start the first call but do not await it — IDs are now in inProgressBlocks
		const firstCall = RiskScoreCollector.collectRiskScoreForBlockedUser( this.window, config );

		// Second call with the same IDs — already in inProgressBlocks, should not start a new batch
		RiskScoreCollector.collectRiskScoreForBlockedUser( this.window, config );

		assert.true(
			this.loadAndRenderHCaptcha.calledOnce,
			'loadAndRenderHCaptcha should only be called once for IDs already in progress'
		);

		resolveFirstRequest();
		await firstCall;
	}
);

QUnit.test(
	'collectRiskScoreForBlockedUser enqueues new IDs from a concurrent call and processes them after the current batch',
	async function ( assert ) {
		let resolveFirstRender;
		this.loadAndRenderHCaptcha
			.onFirstCall().returns( new Promise( ( resolve ) => {
				resolveFirstRender = resolve;
			} ) )
			.onSecondCall().returns( Promise.resolve( 'captcha-id-2' ) );
		this.executeHCaptcha.returns( Promise.resolve( 'test-token' ) );

		// Start first call but do not await: [123] is now in inProgressBlocks
		const firstCall = RiskScoreCollector.collectRiskScoreForBlockedUser(
			this.window,
			{
				localBlockIds: [ 123 ],
				globalBlockIds: [],
				siteKey: 'test-site-key'
			}
		);

		// Second call with a new ID while first is still pending: [789] should be enqueued
		RiskScoreCollector.collectRiskScoreForBlockedUser(
			this.window,
			{
				localBlockIds: [ 789 ],
				globalBlockIds: [],
				siteKey: 'test-site-key'
			}
		);

		assert.true(
			this.loadAndRenderHCaptcha.calledOnce,
			'loadAndRenderHCaptcha should not start a second batch while the first is pending'
		);

		// Let the first batch complete
		resolveFirstRender( 'captcha-id-1' );
		await firstCall;

		assert.true(
			this.loadAndRenderHCaptcha.calledTwice,
			'loadAndRenderHCaptcha should be called a second time to process the enqueued IDs'
		);
		assert.deepEqual(
			this.restPost.args[ 1 ][ 1 ].localBlockIds,
			[ 789 ],
			'The second POST should contain only the block ID that was enqueued during the first batch'
		);
	}
);

QUnit.test(
	'collectRiskScoreForBlockedUser correctly deduplicates across a concurrent call with a mixed set of seen and new IDs',
	async function ( assert ) {
		let resolveFirstRender;
		this.loadAndRenderHCaptcha
			.onFirstCall().returns( new Promise( ( resolve ) => {
				resolveFirstRender = resolve;
			} ) )
			.onSecondCall().returns( Promise.resolve( 'captcha-id-2' ) );
		this.executeHCaptcha.returns( Promise.resolve( 'test-token' ) );

		// First call: [123, 456] — both added to inProgressBlocks
		const firstCall = RiskScoreCollector.collectRiskScoreForBlockedUser(
			this.window,
			{ localBlockIds: [ 123, 456 ], globalBlockIds: [], siteKey: 'test-site-key' }
		);

		// Second call while first is pending: 456 is already in inProgressBlocks,
		// 789 and 321 are new and should be enqueued
		RiskScoreCollector.collectRiskScoreForBlockedUser(
			this.window,
			{ localBlockIds: [ 456, 789, 321 ], globalBlockIds: [], siteKey: 'test-site-key' }
		);

		// Let the first batch complete
		resolveFirstRender( 'captcha-id-1' );
		await firstCall;

		assert.deepEqual(
			this.restPost.args[ 0 ][ 1 ].localBlockIds,
			[ 123, 456 ],
			'First POST should contain the IDs from the first call'
		);
		assert.deepEqual(
			this.restPost.args[ 1 ][ 1 ].localBlockIds,
			[ 789, 321 ],
			'Second POST should contain only the IDs from the second call that were not already in progress'
		);
	}
);

QUnit.test(
	'collectRiskScoreForBlockedUser does not call the REST endpoint when loadAndRenderHCaptcha rejects',
	async function ( assert ) {
		this.loadAndRenderHCaptcha.returns(
			Promise.reject( 'render-error' )
		);

		await RiskScoreCollector.collectRiskScoreForBlockedUser(
			this.window,
			{
				localBlockIds: [ 123 ],
				globalBlockIds: [],
				siteKey: 'test-site-key'
			}
		);

		assert.true(
			this.restPost.notCalled,
			'api.post should not be called when rendering fails'
		);
	}
);
