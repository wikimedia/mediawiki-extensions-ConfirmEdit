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

	private readonly HookRunner $hookRunner;

	public function __construct(
		Parameters $parameters,
		HookContainer $hookContainer,
		private readonly CaptchaFactory $captchaFactory,
		private readonly UserFactory $userFactory,
	) {
		parent::__construct( $parameters );
		$this->hookRunner = new HookRunner( $hookContainer );
	}

	public function execute(): bool {
		$action = $this->parameters->getAction();
		$filterId = $this->parameters->getFilter()->getID();
		$userIdentity = $this->parameters->getUser();

		$captcha = $this->captchaFactory->getGlobalInstance( $action );
		$captcha->setAction( $action );

		$actionSupported = in_array( $action, CaptchaTriggers::CAPTCHA_TRIGGERS, true );
		if ( !$this->hookRunner->onConfirmEditBeforeForceShowCaptcha(
			$userIdentity, $action, $actionSupported
		) ) {
			return false;
		}

		if ( !$actionSupported ) {
			LoggerFactory::getInstance( 'ConfirmEdit' )->error(
				'Filter {filter}: {action} is not defined in the list of triggers known to ConfirmEdit',
				[ 'action' => $action, 'filter' => $filterId ]
			);

			return false;
		}

		RequestContext::getMain()->getRequest()->getSession()->set(
			self::FILTER_ID_SESSION_KEY,
			$filterId
		);
		$captcha->setForceShowCaptcha( true );

		// 'edit' action in AbuseFilter means 'edit' and 'create' for ConfirmEdit
		if ( $action === CaptchaTriggers::EDIT ) {
			$createCaptcha = $this->captchaFactory->getGlobalInstance( CaptchaTriggers::CREATE );
			$createCaptcha->setForceShowCaptcha( true );
		}

		// If the CAPTCHA was already solved or the user is known to skip
		// captchas (see SimpleCaptcha::shouldSkipCaptcha), then don't log that a
		// CAPTCHA was shown because the consequence would have had no effect
		$user = $this->userFactory->newFromUserIdentity( $userIdentity );
		if ( $captcha->isCaptchaSolved() || $captcha->shouldSkipCaptcha( $user ) ) {
			return false;
		}

		return true;
	}
}
