<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Hooks\Handlers;

use MediaWiki\Config\Config;
use MediaWiki\Permissions\Hook\UserGetRightsHook;

class UserGetRightsHookHandler implements UserGetRightsHook {

	public function __construct(
		private readonly Config $config,
	) {
	}

	/**
	 * If a minimum edit count is set, only respect the skipcaptcha right if the user meets it
	 *
	 * @inheritDoc
	 */
	public function onUserGetRights( $user, &$rights ) {
		$minEditCount = $this->config->get( 'SkipCaptchaMinimumEditCount' );
		if ( $minEditCount && $user->getEditCount() < $minEditCount ) {
			$rights = array_diff( $rights, [ 'skipcaptcha' ] );
		}
	}

}
