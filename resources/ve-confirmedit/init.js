mw.loader.using( 'ext.visualEditor.targetLoader' ).then( () => {
	mw.libs.ve.targetLoader.addPlugin( () => {
		require( './ve.init.mw.CaptchaSaveErrorHandler.js' )();
	} );
} );
