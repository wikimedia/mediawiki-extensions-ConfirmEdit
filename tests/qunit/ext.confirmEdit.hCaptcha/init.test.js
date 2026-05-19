'use strict';

const { initEditorIntegrations } = require( 'ext.confirmEdit.hCaptcha/ext.confirmEdit.hCaptcha/init.js' );

QUnit.module( 'ext.confirmEdit.hCaptcha.init', QUnit.newMwEnvironment() );

QUnit.test.if(
	'registers VE plugins when VE is available',
	mw.loader.getState( 'ext.visualEditor.targetLoader' ),
	function ( assert ) {
		this.sandbox.stub( mw.loader, 'getState' )
			.withArgs( 'ext.visualEditor.targetLoader' ).returns( 'registered' );
		const loaderUsing = this.sandbox.stub( mw.loader, 'using' )
			.returns( Promise.resolve() );

		initEditorIntegrations();

		assert.true(
			loaderUsing.calledWith( 'ext.visualEditor.targetLoader' ),
			'mw.loader.using should be called with ext.visualEditor.targetLoader'
		);
	}
);

QUnit.test(
	'does not register VE plugins when VE module state is missing',
	function ( assert ) {
		this.sandbox.stub( mw.loader, 'getState' )
			.withArgs( 'ext.visualEditor.targetLoader' ).returns( 'missing' );
		const loaderUsing = this.sandbox.stub( mw.loader, 'using' )
			.returns( Promise.resolve() );

		initEditorIntegrations();

		assert.true(
			loaderUsing.notCalled,
			'mw.loader.using should not be called when VE is missing'
		);
	}
);
