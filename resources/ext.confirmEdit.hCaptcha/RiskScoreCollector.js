const utils = require( './utils.js' );

function collectRiskScoreForBlockedUser( win, blockedIpEditingScoreCollectionConfig ) {
	const {
		globalBlockIds,
		localBlockIds,
		siteKey
	} = blockedIpEditingScoreCollectionConfig;

	const hasLocalBlocks = ( localBlockIds && localBlockIds.length > 0 );
	const hasGlobalBlocks = ( globalBlockIds && globalBlockIds.length > 0 );

	if ( !siteKey || ( !hasLocalBlocks && !hasGlobalBlocks ) ) {
		return;
	}

	const interfaceName = 'blocked-ip-risk-score';

	const container = win.document.createElement( 'div' );
	container.setAttribute( 'data-sitekey', siteKey );

	win.document.body.appendChild( container );

	const loader = utils.loadAndRenderHCaptcha(
		win,
		interfaceName,
		container
	);

	return loader.then( ( captchaId ) => utils.executeHCaptcha(
		win,
		captchaId,
		interfaceName
	) ).then( ( responseToken ) => {
		const api = new mw.Rest();

		return api.post( '/confirmedit/v0/hcaptcha/blocktoken', {
			riskScoreToken: responseToken,
			globalBlockIds: globalBlockIds,
			localBlockIds: localBlockIds,
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
		} );
	} ).catch( ( errorCode ) => mw.track(
		'confirmEdit.hCaptchaRenderCallback',
		'error',
		interfaceName,
		errorCode
	) );
}

module.exports = {
	collectRiskScoreForBlockedUser
};
