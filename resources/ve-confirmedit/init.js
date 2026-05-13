mw.loader.using( 'ext.visualEditor.targetLoader' ).then( () => {
	mw.libs.ve.targetLoader.addPlugin( () => {
		// Guard against multiple invocations in the same page load, because multiple
		// calls to the static.init method will break the save process
		if ( ve.init.mw.CaptchaSaveErrorHandler ) {
			return;
		}

		require( './ve.init.mw.CaptchaSaveErrorHandler.js' )();

		ve.init.mw.CaptchaSaveErrorHandler.static.init();
	} );
} );
