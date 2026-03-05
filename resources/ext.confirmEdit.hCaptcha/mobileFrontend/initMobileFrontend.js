/**
 * @typedef ConfirmEditHCaptchaConfig
 * @property {string} HCaptchaApiUrl URL to use for accessing hCaptcha API
 * @property {string} HCaptchaSiteKey SiteKey to use when accessing the hCaptcha API
 * @property {boolean} HCaptchaEnterprise Whether to use hCaptcha Enterprise
 * @property {boolean} HCaptchaSecureEnclave Whether to use Secure Enclave
 * @property {boolean} HCaptchaEnabledInMobileFrontend Whether to use hCaptcha in the MobileFrontend
 */

/**
 * Initialization function for hCaptcha in the MobileFrontend.
 *
 * This function is called by init.js, and is responsible for setting hook
 * handlers that modify the behavior of the MobileFrontend in order to render
 * the hCaptcha code in the SourceEditor.
 *
 * @param {string} interfaceName Name hCaptcha is being loaded for.
 * @param {ConfirmEditHCaptchaConfig} config Configuration parameters.
 * @param {Window} windowObject Reference to the browser window.
 */
module.exports = function (
	interfaceName,
	config,
	windowObject
) {
	const mobileFrontendSecureEnclave = require( './mobileFrontendSecureEnclave.js' );
	const { loadHCaptcha } = require( '../utils.js' );

	if ( config.HCaptchaEnabledInMobileFrontend ) {
		let hookPayload = null;
		let hCaptchaLoader;

		/**
		 * Used to ensure loadHCaptcha() has completed before running further
		 * actions while also preventing it from running more than once.
		 *
		 * @return {Promise<void>}
		 */
		const doLoadHCaptcha = () => {
			if ( !hCaptchaLoader ) {
				hCaptchaLoader = loadHCaptcha( windowObject, interfaceName );
			}

			return hCaptchaLoader;
		};

		mw.hook( 'mobileFrontend.sourceEditor.getDefaultOptions' ).add( ( e ) => {
			const newDefaults = Object.assign(
				{},
				e.defaults,
				{
					hCaptchaLicenseText: mw.message( 'hcaptcha-privacy-policy' ).parse(),
					hCaptchaSiteKey: config.HCaptchaSiteKey
				}
			);

			e.setDefaults( newDefaults );
		} );
		mw.hook( 'mobileFrontend.sourceEditor.getSavePanelTemplateSource' ).add( ( e ) => {
			// The usage of the "triple mustache" syntax (i.e. {{{variable}}})
			// for the license text is intentional: By not escaping these variables,
			// we prevent the template engine from tampering the value of the
			// license text, so that we can provide a value that includes HTML code
			// linking to license terms. Normally that HTML will come from a call to
			// mw.message().parse() instead of being user-provided HTML, so this is
			// considered safe.
			e.setTemplate( `
					<form id="h-captcha-container-form">
						<div id="h-captcha" class="h-captcha" data-size="invisible"
							data-sitekey="{{hCaptchaSiteKey}}">
						</div>
						<div class="ext-confirmEdit-captcha-privacy-policy">
							{{{hCaptchaLicenseText}}}
						</div>
					</form>`
			);
		} );
		mw.hook( 'mobileFrontend.sourceEditor.getCaptchaPanelTemplateSource' ).add( ( e ) => {
			// Removes the default captcha code from the MobileEditor, as the code
			// for hCaptcha is included in the EditSummary panel instead.
			e.setTemplate( '', false );
		} );
		mw.hook( 'mobileFrontend.sourceEditor.preRenderFinished' ).add( () => {
			// Preloads the hCaptcha script after the MobileFrontend templates
			// have been evaluated.
			doLoadHCaptcha();
		} );
		mw.hook( 'mobileFrontend.sourceEditor.saveBegin' ).add( ( e ) => {
			hookPayload = e;

			// Stop the regular MobileFrontend save flow.
			e.stop();

			// Calling doLoadHCaptcha() ensures the SDK is loaded before running
			// an hCaptcha workflow, as it will either wait for a previous load
			// attempt to complete (likely the one from the preRenderFinished
			// hook handler), or load it ad hoc if needed.
			doLoadHCaptcha().then(
				() => mobileFrontendSecureEnclave(
					windowObject,
					interfaceName
				)
			);
		} );
		mw.hook( 'confirmEdit.hCaptcha.executionSuccess' ).add( ( token ) => {
			if ( hookPayload === null ) {
				return;
			}

			const payload = hookPayload;
			hookPayload = null;

			payload.options.captchaWord = token;
			payload.resume( payload.options );
		} );
	}
};
