<?php

namespace MediaWiki\Extension\ConfirmEdit\AbuseFilter;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Consequence;

/**
 * Will inform ConfirmEdit extension to show a CAPTCHA.
 */
class CaptchaConsequence extends Consequence {

	public const FLAG = 'wgAbuseFilterCaptchaConsequence';

	public function execute(): bool {
		// This consequence was triggered, so we need to set a global flag
		// which Extension:ConfirmEdit will read in order to decide if a
		// CAPTCHA should be shown to the user in onConfirmEditTriggersCaptcha
		RequestContext::getMain()->getRequest()->setVal( self::FLAG, true );
		return true;
	}
}
