<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Hooks;

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
	 * for the provided action.
	 *
	 * @param UserIdentity $userIdentity The user who would see the CAPTCHA
	 * @param string $action Action user is performing, one of the constants in
	 *   {@link CaptchaTriggers} or another action defined by another extension
	 * @return bool|void Return false to prevent the flag from being set
	 */
	public function onConfirmEditBeforeForceShowCaptcha(
		UserIdentity $userIdentity,
		string $action
	);
}
