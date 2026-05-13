const utils = require( './utils.js' );

function collectRiskScoreForBlockedUser( win, blockedIpEditingScoreCollectionConfig ) {
	const { blockIds, siteKey } = blockedIpEditingScoreCollectionConfig;

	if ( !siteKey || !blockIds || blockIds.length === 0 ) {
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
	) ).catch( ( errorCode ) => mw.track(
		'confirmEdit.hCaptchaRenderCallback',
		'error',
		interfaceName,
		errorCode
	) );
}

module.exports = {
	collectRiskScoreForBlockedUser
};
