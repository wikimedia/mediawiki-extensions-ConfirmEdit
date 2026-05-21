const utils = require( './utils.js' );

let submittedBlocks = {
	local: [],
	global: []
};

// Tracks block IDs that have been queued or are currently being sent. Used to
// prevent duplicate submissions when this function is called concurrently.
let inProgressBlocks = {
	local: [],
	global: []
};

let mutex;

function collectRiskScoreForBlockedUser( win, blockedIpEditingScoreCollectionConfig ) {
	const { globalBlockIds, localBlockIds } = blockedIpEditingScoreCollectionConfig;
	const siteKey = blockedIpEditingScoreCollectionConfig.siteKey;
	const hasLocalBlocks = ( localBlockIds && localBlockIds.length > 0 );
	const hasGlobalBlocks = ( globalBlockIds && globalBlockIds.length > 0 );

	if ( !siteKey || ( !hasLocalBlocks && !hasGlobalBlocks ) ) {
		return;
	}

	// When opening a page with action=edit in the URL, the MobileFrontend will
	// open the editor, detect the block and then go back to the article page
	// to show the blocked edit notice. When that happens, it will trigger this
	// function twice, so we need to track which blocks have been already sent.
	//
	// Note that this filtering and the subsequent push to inProgressBlocks must
	// remain synchronous so that concurrent calls see a consistent in-progress
	// set.
	const newLocalBlocks = localBlockIds.filter(
		( id ) => !submittedBlocks.local.includes( id ) && !inProgressBlocks.local.includes( id )
	);
	const newGlobalBlocks = globalBlockIds.filter(
		( id ) => !submittedBlocks.global.includes( id ) && !inProgressBlocks.global.includes( id )
	);

	if ( newLocalBlocks.length === 0 && newGlobalBlocks.length === 0 ) {
		return;
	}

	inProgressBlocks.local.push( ...newLocalBlocks );
	inProgressBlocks.global.push( ...newGlobalBlocks );

	if ( mutex ) {
		// IDs will be processed once the current request finishes.
		return;
	}

	return processQueue( win, siteKey );
}

function processQueue( win, siteKey ) {
	if ( inProgressBlocks.local.length === 0 && inProgressBlocks.global.length === 0 ) {
		return;
	}

	const batchLocalBlocks = [ ...inProgressBlocks.local ];
	const batchGlobalBlocks = [ ...inProgressBlocks.global ];

	const interfaceName = 'blocked-ip-risk-score';

	const container = win.document.createElement( 'div' );
	container.setAttribute( 'data-sitekey', siteKey );
	win.document.body.appendChild( container );

	const loader = utils.loadAndRenderHCaptcha(
		win,
		interfaceName,
		container
	);

	mutex = loader.then( ( captchaId ) => utils.executeHCaptcha(
		win,
		captchaId,
		interfaceName
	) ).then( ( responseToken ) => {
		const api = new mw.Rest();

		return api.post( '/confirmedit/v0/hcaptcha/blocktoken', {
			riskScoreToken: responseToken,
			globalBlockIds: batchGlobalBlocks,
			localBlockIds: batchLocalBlocks,
			pageViewId: mw.user.getPageviewToken()
		} ).then( () => {
			submittedBlocks.local.push( ...batchLocalBlocks );
			submittedBlocks.global.push( ...batchGlobalBlocks );
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
	) ).then( () => {
		// Remove the batch from inProgressBlocks regardless of success or failure,
		// then check whether any IDs were enqueued while the batch was running.
		inProgressBlocks.local = inProgressBlocks.local.filter(
			( id ) => !batchLocalBlocks.includes( id )
		);
		inProgressBlocks.global = inProgressBlocks.global.filter(
			( id ) => !batchGlobalBlocks.includes( id )
		);
		mutex = undefined;
		return processQueue( win, siteKey );
	} );

	return mutex;
}

/**
 * To be used only by tests.
 *
 * @internal
 */
function reset() {
	submittedBlocks = {
		local: [],
		global: []
	};
	inProgressBlocks = {
		local: [],
		global: []
	};
	mutex = undefined;
}

module.exports = {
	collectRiskScoreForBlockedUser,
	reset
};
