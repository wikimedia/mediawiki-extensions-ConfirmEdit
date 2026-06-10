function initEditorIntegrations() {
	const useSecureEnclave = require( './secureEnclave.js' );
	const RiskScoreCollector = require( './RiskScoreCollector.js' );
	const initMobileFrontend = require( './mobileFrontend/initMobileFrontend.js' );
	const visualEditorInitPluginsCallback = require( './ve/initPlugins.js' );
	const config = require( './config.json' );

	// If HCaptchaEnterprise and Secure Enclave are enabled, decide whether to
	// load support for either the MobileFrontend or the Visual Editor.
	//
	// Note support for the MobileFrontend is loaded if that extension is
	// loading, loaded, executing or ready. Notably, MobileFrontend support is
	// not loaded if the extension is just "registered", as that state means
	// MobileFrontend is known but the user is using another UI (such as the
	// source editor for Desktop or the VisualEditor); if that's the case, this
	// initializes the support for the Desktop editor instead.
	//
	// If the module has been loaded just for collecting a risk score, this
	// calls the method for doing so without calling the methods to initialize
	// support for the editing interfaces.
	if ( config.HCaptchaEnterprise && config.HCaptchaSecureEnclave ) {
		const blockedIpEditingScoreCollectionSiteKey = mw.config.get(
			'wgHCaptchaBlockedIpEditingScoreCollectionSiteKey'
		);
		if ( blockedIpEditingScoreCollectionSiteKey ) {
			RiskScoreCollector.collectRiskScoreForBlockedUser(
				window,
				blockedIpEditingScoreCollectionSiteKey
			);
		}

		const mobileFEState = mw.loader.getState( 'mobile.editor.overlay' );
		const isMobileFEActive = [ 'loading', 'loaded', 'ready', 'executing' ].includes( mobileFEState );
		const isMobileHCaptchaAbuseFilterEnabled = mw.config.get( 'wgConfirmEditMobileHCaptchaAbuseFilterEnabled' );

		if ( isMobileFEActive || isMobileHCaptchaAbuseFilterEnabled ) {
			// Editing interfaces may require a specific key: Override the
			// general key provided by RLRegisterModulesHandler by one specific
			// for the current action if such key was provided by
			// MakeGlobalVariablesScriptHookHandler.
			const editSiteKey = mw.config.get( 'wgConfirmEditHCaptchaSiteKey' );

			const editConfig = Object.assign(
				{},
				config,
				{
					MobileHCaptchaAbuseFilterEnabled: !!isMobileHCaptchaAbuseFilterEnabled
				}
			);

			if ( editSiteKey ) {
				editConfig.HCaptchaSiteKey = editSiteKey;
			}

			initMobileFrontend( 'mobilefrontendeditor', editConfig, window );
		}

		// Perform initialization for all interfaces other than VisualEditor and MobileFrontend
		// This is run even if the user is in mobile mode, because a user may be using the
		// Vector skin in mobile mode.
		useSecureEnclave( window );
	}

	// Register the hCaptcha VisualEditor plugins that handle showing hCaptcha
	// for both a save error and before the first save attempt in VisualEditor.
	// This is skipped if the VisualEditor is not installed (when the state
	// is 'missing') or if it's errored out.
	//
	// These should always be registered as the VisualEditor plugin code will
	// handle when to show the CAPTCHA (and will avoid loading the hCaptcha SDK
	// in cases where it is not needed)
	const visualEditorModuleState = mw.loader.getState( 'ext.visualEditor.targetLoader' );
	if ( !visualEditorModuleState || visualEditorModuleState === 'missing' || visualEditorModuleState === 'error' ) {
		return;
	}

	mw.loader.using( 'ext.visualEditor.targetLoader' ).then( () => {
		mw.libs.ve.targetLoader.addPlugin( visualEditorInitPluginsCallback );
	} );
}

$( () => {
	initEditorIntegrations();
} );

/**
 * Utils used to render and execute hCaptcha in the
 * {@link mw.libs.confirmEdit.CaptchaWidget} class.
 * Not for use elsewhere and methods provided by this module may break without notice.
 *
 * @internal
 */
module.exports = {
	utils: require( './utils.js' ),
	initEditorIntegrations: initEditorIntegrations
};
