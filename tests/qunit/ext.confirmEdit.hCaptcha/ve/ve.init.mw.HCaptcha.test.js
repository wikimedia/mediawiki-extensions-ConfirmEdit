QUnit.module.if( 'ext.confirmEdit.hCaptcha.ve.HCaptcha', mw.loader.getState( 'ext.visualEditor.targetLoader' ), QUnit.newMwEnvironment(), ( hooks ) => {

	const hCaptchaUtils = require( 'ext.confirmEdit.hCaptcha/ext.confirmEdit.hCaptcha/utils.js' );
	const hCaptcha = require( 'ext.confirmEdit.hCaptcha/ext.confirmEdit.hCaptcha/ve/ve.init.mw.HCaptcha.js' );

	hooks.beforeEach( function () {
		this.executeHCaptcha = this.sandbox.stub( hCaptchaUtils, 'executeHCaptcha' );
		this.resetHCaptcha = this.sandbox.stub( hCaptchaUtils, 'resetHCaptcha' );
	} );

	QUnit.test( 'onSaveOptionsProcess rejects if onSaveWorkflowEnd called', function ( assert ) {
		const unresolvedPromise = new Promise( () => {
		} );
		this.executeHCaptcha.returns( unresolvedPromise );

		hCaptcha();

		const $qunitFixture = $( '#qunit-fixture' );
		const target = {
			saveFields: {},
			saveDialog: {
				$element: $qunitFixture,
				updateSize: this.sandbox.stub(),
				popPending: this.sandbox.stub(),
				clearMessage: this.sandbox.stub(),
				executeAction: this.sandbox.stub(),
				isOpened: this.sandbox.stub(),
				showMessage: ( name, $element ) => {
					$qunitFixture.append( $element );
				}
			},
			emit: this.sandbox.stub()
		};

		ve.init.mw.HCaptcha.static.widgetId = 'test-widget-id';

		// Call onSaveOptionsProcess and expect a pending promise is returned
		const saveOptionsPromise = ve.init.mw.HCaptcha.static.onSaveOptionsProcess( target );

		assert.true(
			this.executeHCaptcha.calledOnce,
			'executeHCaptcha is called when onSaveOptionsProcess is called'
		);
		assert.deepEqual(
			this.executeHCaptcha.firstCall.args,
			[ window, 'test-widget-id', 'visualeditor' ],
			'executeHCaptcha arguments are as expected'
		);

		let saveOptionsPromiseState = 'pending';
		saveOptionsPromise.then(
			() => {
				saveOptionsPromiseState = 'fulfilled';
			},
			() => {
				saveOptionsPromiseState = 'rejected';
			}
		);
		assert.strictEqual(
			saveOptionsPromiseState,
			'pending',
			'The promise returned by onSaveOptionsProcess should still be pending'
		);

		// Call onSaveWorkflowEnd and expect that the promise is rejected
		ve.init.mw.HCaptcha.static.onSaveWorkflowEnd( target );
		assert.rejects(
			saveOptionsPromise,
			/Save dialog closed mid execution/,
			'The promise returned by onSaveOptionsProcess should be rejected if onSaveWorkflowEnd is called'
		);
	} );

	QUnit.test.each( 'onSaveError resets the widget only when a token has been consumed', {
		'widget and token are both set': {
			widgetId: 'test-widget-id',
			hCaptchaResponseToken: 'token-A',
			expectReset: true
		},
		'no token has been consumed': {
			widgetId: 'test-widget-id',
			hCaptchaResponseToken: null,
			expectReset: false
		},
		'widget is not initialised': {
			widgetId: null,
			hCaptchaResponseToken: 'token-A',
			expectReset: false
		}
	}, function ( assert, options ) {
		hCaptcha();

		ve.init.mw.HCaptcha.static.widgetId = options.widgetId;
		ve.init.mw.HCaptcha.static.hCaptchaResponseToken = options.hCaptchaResponseToken;

		ve.init.mw.HCaptcha.static.onSaveError();

		if ( options.expectReset ) {
			assert.strictEqual(
				ve.init.mw.HCaptcha.static.hCaptchaResponseToken,
				null,
				'the held token is cleared so the next attempt re-executes hCaptcha'
			);
			assert.true(
				this.resetHCaptcha.calledOnceWith( window, options.widgetId ),
				'resetHCaptcha is called once to reset the widget'
			);
		} else {
			assert.true(
				this.resetHCaptcha.notCalled,
				'resetHCaptcha is not called when there is no consumed token or widget'
			);
			assert.strictEqual(
				ve.init.mw.HCaptcha.static.hCaptchaResponseToken,
				options.hCaptchaResponseToken,
				'the held token is left unchanged when the early-return guard short-circuits'
			);
		}
	} );

	QUnit.test( 'init wires saveError on article targets to onSaveError', function ( assert ) {
		hCaptcha();

		// Intercept only the 've.newTarget' hook so we can capture its callback and
		// invoke it directly. Firing the hook globally would also run other
		// extensions' listeners and crash the test run.
		let veNewTargetCallback;
		const hookStub = this.sandbox.stub( mw, 'hook' );
		hookStub.callThrough();
		hookStub.withArgs( 've.newTarget' ).returns( {
			add: ( callback ) => {
				veNewTargetCallback = callback;
			}
		} );

		ve.init.mw.HCaptcha.static.init();

		const handlers = {};
		const target = {
			constructor: { static: { name: 'article' } },
			on: ( event, cb ) => {
				handlers[ event ] = cb;
			},
			emit: ( event ) => {
				handlers[ event ]();
			},
			getSaveOptionsProcess: () => ( { next: this.sandbox.stub() } )
		};

		// Register ConfirmEdit's own listeners without firing the global hook.
		veNewTargetCallback( target );

		ve.init.mw.HCaptcha.static.widgetId = 'test-widget-id';
		ve.init.mw.HCaptcha.static.hCaptchaResponseToken = 'token-A';

		target.emit( 'saveError' );

		assert.strictEqual(
			ve.init.mw.HCaptcha.static.hCaptchaResponseToken,
			null,
			'emitting saveError clears the held token via onSaveError'
		);
		assert.true(
			this.resetHCaptcha.calledOnceWith( window, 'test-widget-id' ),
			'emitting saveError resets the widget via onSaveError'
		);
	} );
} );
