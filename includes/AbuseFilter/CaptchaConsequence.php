<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\AbuseFilter;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Consequence;
use MediaWiki\Extension\AbuseFilter\Consequences\Parameters;
use MediaWiki\Extension\ConfirmEdit\CaptchaTriggers;
use MediaWiki\Extension\ConfirmEdit\Hooks\HookRunner;
use MediaWiki\Extension\ConfirmEdit\Services\CaptchaFactory;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\User\UserFactory;

/**
 * Show a CAPTCHA to the user before they can proceed with an action.
 */
class CaptchaConsequence extends Consequence {
	/**
	 * The session key that stores the ID of the last Abuse Filter that
	 * triggered the captcha consequence.
	 *
	 * This is used in CaptchaScoreHooks to check which abuse filter triggered
	 * a captcha challenge after an edit, so that the filter ID can be properly
	 * logged as part of the error.
	 */
	public const FILTER_ID_SESSION_KEY = 'captchaConsequence-filterId';

	public function __construct(
		Parameters $parameters,
		private readonly HookContainer $hookContainer,
		private readonly CaptchaFactory $captchaFactory,
		private readonly UserFactory $userFactory,
	) {
		parent::__construct( $parameters );
	}

	public function execute(): bool {
		$action = $this->parameters->getAction();
		$filterId = $this->parameters->getFilter()->getID();
		$userIdentity = $this->parameters->getUser();

		if ( !in_array( $action, CaptchaTriggers::CAPTCHA_TRIGGERS ) ) {
			LoggerFactory::getInstance( 'ConfirmEdit' )->error(
				'Filter {filter}: {action} is not defined in the list of triggers known to ConfirmEdit',
				[ 'action' => $action, 'filter' => $filterId ]
			);

			return false;
		}

		$captcha = $this->captchaFactory->getGlobalInstance( $action );
		$captcha->setAction( $action );

		$hookRunner = new HookRunner( $this->hookContainer );
		if ( !$hookRunner->onConfirmEditBeforeForceShowCaptcha(
			$userIdentity, $action
		) ) {
			return false;
		}

		RequestContext::getMain()->getRequest()->getSession()->set(
			self::FILTER_ID_SESSION_KEY,
			$filterId
		);
		$captcha->setForceShowCaptcha( true );

		// If the CAPTCHA was already solved or the user is known to skip
		// captchas (see SimpleCaptcha::shouldCheck), then don't log that a
		// CAPTCHA was shown because the consequence would have had no effect
		$user = $this->userFactory->newFromUserIdentity( $userIdentity );
		if (
			$captcha->isCaptchaSolved() ||
			$user->isSystemUser() ||
			$user->isBot()
		) {
			return false;
		}

		return true;
	}
}
