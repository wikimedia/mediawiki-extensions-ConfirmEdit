QUnit.module.if( 'ext.confirmEdit.hCaptcha.ve.HCaptchaCollectRiskScore', mw.loader.getState( 'ext.visualEditor.targetLoader' ), QUnit.newMwEnvironment(), ( hooks ) => {
	const collectRiskScoreHandler = require( 'ext.confirmEdit.hCaptcha/ext.confirmEdit.hCaptcha/ve/ve.init.mw.HCaptchaCollectRiskScore.js' );
	const RiskScoreCollector = require( 'ext.confirmEdit.hCaptcha/ext.confirmEdit.hCaptcha/RiskScoreCollector.js' );

	hooks.beforeEach( function () {
		this.collectRiskScoreForBlockedUser = this.sandbox.stub(
			RiskScoreCollector,
			'collectRiskScoreForBlockedUser'
		);
	} );

	/**
	 * Returns a fake mw.hook for 've.newTarget' that records add() callbacks and
	 * exposes a fire() that only calls those callbacks — preventing other extensions'
	 * global listeners (e.g. Cite) from running in the test environment.
	 *
	 * The sandbox stub is set up before init() is called so that init()'s
	 * mw.hook('ve.newTarget').add(...) registers against the fake hook.
	 */
	function stubVeNewTargetHook( sandbox ) {
		const callbacks = [];
		const fakeHook = {
			add: ( cb ) => {
				callbacks.push( cb );
			},
			fire: ( ...args ) => {
				callbacks.forEach( ( cb ) => cb( ...args ) );
			}
		};
		const hookStub = sandbox.stub( mw, 'hook' );
		hookStub.callThrough();
		hookStub.withArgs( 've.newTarget' ).returns( fakeHook );
		return fakeHook;
	}

	QUnit.test.each( 'onActivationComplete calls collectRiskScoreForBlockedUser based on canEdit and block config', {
		'target can edit, config present': {
			canEdit: true,
			config: {
				siteKey: 'test-key',
				localBlockIds: [ 1 ],
				globalBlockIds: []
			},
			shouldCollect: false
		},
		'target cannot edit, no block config': {
			canEdit: false,
			config: null,
			shouldCollect: false
		},
		'target cannot edit, block config present': {
			canEdit: false,
			config: {
				siteKey: 'test-key',
				localBlockIds: [ 1 ],
				globalBlockIds: []
			},
			shouldCollect: true
		}
	}, function ( assert, options ) {
		collectRiskScoreHandler();

		this.sandbox.stub( mw.config, 'get' )
			.withArgs( 'wgHCaptchaBlockedIpEditingScoreCollectionConfig' )
			.returns( options.config );

		ve.init.mw.HCaptchaCollectRiskScore.static.onActivationComplete(
			{ canEdit: options.canEdit }
		);

		if ( options.shouldCollect ) {
			assert.true(
				this.collectRiskScoreForBlockedUser.calledOnce,
				'collectRiskScoreForBlockedUser is called once'
			);
			assert.deepEqual(
				this.collectRiskScoreForBlockedUser.firstCall.args,
				[ window, options.config ],
				'collectRiskScoreForBlockedUser is called with the expected args'
			);
		} else {
			assert.true(
				this.collectRiskScoreForBlockedUser.notCalled,
				'collectRiskScoreForBlockedUser is not called'
			);
		}
	} );

	QUnit.test( 'init triggers onActivationComplete via surfaceReady for article targets', function ( assert ) {
		collectRiskScoreHandler();

		const fakeHook = stubVeNewTargetHook( this.sandbox );

		const onActivationCompleteSpy = this.sandbox.spy(
			ve.init.mw.HCaptchaCollectRiskScore.static,
			'onActivationComplete'
		);

		ve.init.mw.HCaptchaCollectRiskScore.static.init();

		const fakeTarget = new OO.EventEmitter();
		fakeTarget.canEdit = false;
		fakeTarget.constructor = {
			static: {
				name: 'article'
			}
		};

		fakeHook.fire( fakeTarget );

		assert.true(
			onActivationCompleteSpy.notCalled,
			'onActivationComplete is not called before surfaceReady fires'
		);

		fakeTarget.emit( 'surfaceReady' );

		assert.true(
			onActivationCompleteSpy.calledOnce,
			'onActivationComplete is called once when surfaceReady fires'
		);
		assert.strictEqual(
			onActivationCompleteSpy.firstCall.args[ 0 ],
			fakeTarget,
			'onActivationComplete is called with the target'
		);
	} );

	QUnit.test( 'init ignores non-article targets', function ( assert ) {
		collectRiskScoreHandler();

		const fakeHook = stubVeNewTargetHook( this.sandbox );

		const onActivationCompleteSpy = this.sandbox.spy(
			ve.init.mw.HCaptchaCollectRiskScore.static,
			'onActivationComplete'
		);

		ve.init.mw.HCaptchaCollectRiskScore.static.init();

		const fakeMobileTarget = new OO.EventEmitter();
		fakeMobileTarget.constructor = {
			static: {
				name: 'mobile'
			}
		};

		fakeHook.fire( fakeMobileTarget );
		fakeMobileTarget.emit( 'surfaceReady' );

		assert.true(
			onActivationCompleteSpy.notCalled,
			'onActivationComplete is not called for non-article targets'
		);
	} );
} );
