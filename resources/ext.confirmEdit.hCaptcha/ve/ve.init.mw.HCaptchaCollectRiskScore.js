/**
 * Defines and installs a hook handler for the VisualEditor used to intercept
 * block notices.
 *
 * Returns a callback that should be executed in initPlugins.js after
 * `ve.init.mw.HCaptcha` is loaded.
 */
module.exports = () => {
	const RiskScoreCollector = require( '../RiskScoreCollector.js' );

	ve.init.mw.HCaptchaCollectRiskScore = function () {};

	OO.initClass( ve.init.mw.HCaptchaCollectRiskScore );

	/**
	 * Runs the RiskScoreCollector when a user opens the VisualEditor editor if
	 * a block is preventing the user from editing.
	 *
	 * @param {ve.init.Target} target
	 * @return {void}
	 */
	ve.init.mw.HCaptchaCollectRiskScore.static.onActivationComplete = function ( target ) {
		const config = mw.config.get( 'wgHCaptchaBlockedIpEditingScoreCollectionConfig' );
		if ( !target.canEdit && config ) {
			RiskScoreCollector.collectRiskScoreForBlockedUser(
				window,
				config
			);
		}
	};

	/**
	 * Sets up an event handler for VE surfaceReady event used to trigger the
	 * RiskScore collector when a block notice is shown.
	 */
	ve.init.mw.HCaptchaCollectRiskScore.static.init = function () {
		mw.hook( 've.newTarget' ).add( ( target ) => {
			if ( target.constructor.static.name !== 'article' ) {
				return;
			}
			target.on( 'surfaceReady', () => {
				this.onActivationComplete( target );
			} );
		} );
	};
};
