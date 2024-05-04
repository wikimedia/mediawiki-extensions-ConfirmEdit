<?php

namespace MediaWiki\Extension\ConfirmEdit;

use MediaWiki\Extension\AbuseFilter\Consequences\Parameters;
use MediaWiki\Extension\AbuseFilter\Hooks\AbuseFilterCustomActionsHook;
use MediaWiki\Extension\ConfirmEdit\AbuseFilter\CaptchaConsequence;

class AbuseFilterHooks implements AbuseFilterCustomActionsHook {

	/** @inheritDoc */
	public function onAbuseFilterCustomActions( array &$actions ): void {
		$actions['showcaptcha'] = static function ( Parameters $params ): CaptchaConsequence {
			return new CaptchaConsequence( $params );
		};
	}

}
