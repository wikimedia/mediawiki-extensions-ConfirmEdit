<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit;

use MediaWiki\Config\Config;
use MediaWiki\Extension\AbuseFilter\Consequences\Parameters;
use MediaWiki\Extension\AbuseFilter\Hooks\AbuseFilterCustomActionsHook;
use MediaWiki\Extension\ConfirmEdit\AbuseFilter\CaptchaConsequence;
use MediaWiki\Extension\ConfirmEdit\Services\CaptchaFactory;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\User\UserFactory;

readonly class AbuseFilterHooks implements AbuseFilterCustomActionsHook {

	public function __construct(
		private Config $config,
		private HookContainer $hookContainer,
		private CaptchaFactory $captchaFactory,
		private UserFactory $userFactory,
	) {
	}

	/** @inheritDoc */
	public function onAbuseFilterCustomActions( array &$actions ): void {
		$enabledActions = $this->config->get( 'ConfirmEditEnabledAbuseFilterCustomActions' );
		if ( in_array( 'showcaptcha', $enabledActions ) ) {
			// Messages used: abusefilter-edit-action-showcaptcha, abusefilter-edit-action-showcaptcha-help
			$actions['showcaptcha'] = function ( Parameters $params ): CaptchaConsequence {
				return new CaptchaConsequence(
					$params, $this->hookContainer, $this->captchaFactory, $this->userFactory
				);
			};
		}
	}

}
