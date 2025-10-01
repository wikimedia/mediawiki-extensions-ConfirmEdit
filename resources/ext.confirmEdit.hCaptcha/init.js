$( () => {
	const useSecureEnclave = require( './secureEnclave.js' );
	const config = require( './config.json' );
	const installHCaptchaVisualEditorPlugin = require( './ve/ve.init.mw.HCaptchaSaveErrorHandler.js' );

	if ( config.HCaptchaEnterprise && config.HCaptchaSecureEnclave ) {
		useSecureEnclave( window );
	}

	// If VisualEditor is available, then register the hCaptcha handler plugin.
	// It may be loaded, loading, ready, or registered depending on when this
	// module has been loaded. If it is 'missing' then we should not need to
	// respond to any VisualEditor edit on this page.
	const veState = mw.loader.getState( 'ext.visualEditor.targetLoader' );
	const validStates = [ 'loading', 'loaded', 'ready', 'registered' ];
	if ( validStates.includes( veState ) ) {
		mw.loader.using( 'ext.visualEditor.targetLoader' ).then( () => {
			mw.libs.ve.targetLoader.addPlugin( installHCaptchaVisualEditorPlugin );
		} );
	}
} );
