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

			this.loadAndRenderHCaptcha.returns( Promise.resolve() );
			this.executeHCaptcha.returns( Promise.resolve() );
		},
		afterEach() {
			this.track.restore();
			this.loadAndRenderHCaptcha.restore();
			this.executeHCaptcha.restore();

			this.utils.reset();
		}
	} )
);

QUnit.test.each(
	'collectRiskScoreForBlockedUser returns early when required params are missing',
	{
		'missing blockIds': {
			blockIds: null,
			siteKey: 'test-site-key'
		},
		'empty blockIds': {
			blockIds: [],
			siteKey: 'test-site-key'
		},
		'missing siteKey': {
			blockIds: [ 'test-block-id' ],
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
	function ( assert ) {
		RiskScoreCollector.collectRiskScoreForBlockedUser(
			this.window,
			{
				blockIds: [ 'test-block-id' ],
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
				blockIds: [ 'test-block-id' ],
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
				blockIds: [ 'test-block-id' ],
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
				blockIds: [ 'test-block-id' ],
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
