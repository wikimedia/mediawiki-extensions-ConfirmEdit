const utils = require( './utils.js' );

// Block IDs are resolved server-side, so one submission per page view is
// enough. Holds the pending or completed request; cleared on failure so a
// later call can retry.
let submittedRequest;

function collectRiskScoreForBlockedUser( win, siteKey ) {
	if ( !siteKey || submittedRequest ) {
		return submittedRequest;
	}

	const interfaceName = 'blocked-ip-risk-score';

	const container = win.document.createElement( 'div' );
	container.setAttribute( 'data-sitekey', siteKey );
	win.document.body.appendChild( container );

	submittedRequest = utils.loadAndRenderHCaptcha(
		win,
		interfaceName,
		container
	).then( ( captchaId ) => utils.executeHCaptcha(
		win,
		captchaId,
		interfaceName
	) ).then( ( responseToken ) => {
		const api = new mw.Rest();

		return api.post( '/confirmedit/v0/hcaptcha/blocktoken', {
			riskScoreToken: responseToken,
			page: mw.config.get( 'wgPageName' ),
			pageViewId: mw.user.getPageviewToken()
		} ).catch( ( type, details ) => {
			const loggedError = new Error(
				'Error with type {type} posting block token'
			);
			/* eslint-disable camelcase */
			loggedError.error_context = {
				details: details,
				type: type
			};
			/* eslint-enable camelcase */

			mw.errorLogger.logError( loggedError, 'error.confirmedit' );
			submittedRequest = undefined;
		} );
	} ).catch( ( errorCode ) => {
		mw.track(
			'confirmEdit.hCaptchaRenderCallback',
			'error',
			interfaceName,
			errorCode
		);
		submittedRequest = undefined;
	} );

	return submittedRequest;
}

/**
 * To be used only by tests.
 *
 * @internal
 */
function reset() {
	submittedRequest = undefined;
}

module.exports = {
	collectRiskScoreForBlockedUser,
	reset
};
