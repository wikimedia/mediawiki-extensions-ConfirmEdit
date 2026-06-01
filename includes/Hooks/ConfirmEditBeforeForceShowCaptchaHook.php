<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Hooks;

use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
use MediaWiki\User\UserIdentity;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "ConfirmEditBeforeForceShowCaptcha" to register handlers implementing this interface.
 *
 * @stable to implement
 * @ingroup Hooks
 */
interface ConfirmEditBeforeForceShowCaptchaHook {
	/**
	 * This hook is called before the ConfirmEdit extension sets the "force show captcha" flag
	 * on the provided {@link SimpleCaptcha} instance.
	 *
	 * @param UserIdentity $userIdentity The user who would see the CAPTCHA
	 * @param SimpleCaptcha $captcha The CAPTCHA instance where the flag would be set
	 * @return bool|void Return false to prevent the flag from being set
	 */
	public function onConfirmEditBeforeForceShowCaptcha(
		UserIdentity $userIdentity,
		SimpleCaptcha $captcha
	);
}
