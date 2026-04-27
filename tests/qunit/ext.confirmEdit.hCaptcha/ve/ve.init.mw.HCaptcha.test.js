QUnit.module.if( 'ext.confirmEdit.hCaptcha.ve.HCaptcha', mw.loader.getState( 'ext.visualEditor.targetLoader' ), QUnit.newMwEnvironment(), ( hooks ) => {

	const hCaptchaUtils = require( 'ext.confirmEdit.hCaptcha/ext.confirmEdit.hCaptcha/utils.js' );
	const hCaptcha = require( 'ext.confirmEdit.hCaptcha/ext.confirmEdit.hCaptcha/ve/ve.init.mw.HCaptcha.js' );

	hooks.beforeEach( function () {
		this.executeHCaptcha = this.sandbox.stub( hCaptchaUtils, 'executeHCaptcha' );
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
} );
