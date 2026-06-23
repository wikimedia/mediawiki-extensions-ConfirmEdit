<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Hooks;

use MediaWiki\User\UserIdentity;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "ConfirmEditBeforeForceShowCaptcha" to register handlers implementing this interface.
 *
 * @since 1.47
 * @stable to implement
 * @ingroup Hooks
 */
interface ConfirmEditBeforeForceShowCaptchaHook {
	/**
	 * This hook is called when ConfirmEdit is deciding whether to force show a CAPTCHA
	 * for the provided action.
	 *
	 * Extensions that add their own CAPTCHA triggers should handle this hook if the
	 * CAPTCHA trigger supports force shown CAPTCHAs, setting
	 * $actionSupportedForForceShowCaptcha to true for an action that is not one of the
	 * built-in {@link CaptchaTriggers}.
	 *
	 * @param UserIdentity $userIdentity The user who would see the CAPTCHA
	 * @param string $action Action user is performing, one of the constants in
	 *   {@link CaptchaTriggers} or another action defined by another extension
	 * @param bool &$actionSupportedForForceShowCaptcha Whether $action supports a force
	 *   shown CAPTCHA. True on entry for a built-in trigger; set it to true to claim
	 *   another extension's action. It may not arrive as false (a prior handler may have
	 *   set it), so only ever set it to true — to abort instead, return false.
	 * @return bool|void Return false to prevent the CAPTCHA from being force shown
	 */
	public function onConfirmEditBeforeForceShowCaptcha(
		UserIdentity $userIdentity,
		string $action,
		bool &$actionSupportedForForceShowCaptcha
	);
}
