<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Auth;

use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;

/**
 * Allows the construction of a {@link LoginAttemptCounter} instance
 *
 * @internal
 * @since 1.47
 */
class LoginAttemptCounterFactory {
	/**
	 * Creates a new {@link LoginAttemptCounter} instance for the provided {@link SimpleCaptcha}.
	 */
	public function newLoginAttemptCounter( SimpleCaptcha $captcha ): LoginAttemptCounter {
		return new LoginAttemptCounter( $captcha );
	}
}
