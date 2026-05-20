<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Services;

use BadMethodCallException;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Context\IContextSource;
use MediaWiki\Extension\ConfirmEdit\Auth\LoginAttemptCounterFactory;
use MediaWiki\Extension\ConfirmEdit\CaptchaTriggers;
use MediaWiki\Extension\ConfirmEdit\FancyCaptcha\FancyCaptcha;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\HCaptcha;
use MediaWiki\Extension\ConfirmEdit\Hooks\HookRunner;
use MediaWiki\Extension\ConfirmEdit\QuestyCaptcha\QuestyCaptcha;
use MediaWiki\Extension\ConfirmEdit\ReCaptchaNoCaptcha\ReCaptchaNoCaptcha;
use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
use MediaWiki\Extension\ConfirmEdit\Turnstile\Turnstile;
use MediaWiki\HookContainer\HookContainer;

/**
 * Allows a caller to fetch the global {@link SimpleCaptcha} instance for the provided action.
 *
 * @since 1.47
 */
class CaptchaFactory {
	public const array CONSTRUCTOR_OPTIONS = [
		'CaptchaClass',
		'CaptchaTriggers',
	];

	private const array CAPTCHA_NAME_TO_CLASS = [
		'SimpleCaptcha' => SimpleCaptcha::class,
		'FancyCaptcha' => FancyCaptcha::class,
		'QuestyCaptcha' => QuestyCaptcha::class,
		'ReCaptchaNoCaptcha' => ReCaptchaNoCaptcha::class,
		'HCaptcha' => HCaptcha::class,
		'Turnstile' => Turnstile::class,
	];

	/**
	 * @var SimpleCaptcha[][] Captcha instances, where the keys are action => captcha type and the
	 *   values are an instance of that captcha type.
	 */
	private static array $globalInstances = [];

	public function __construct(
		private readonly ServiceOptions $options,
		private readonly HookContainer $hookContainer,
		private readonly LoginAttemptCounterFactory $loginAttemptCounterFactory,
	) {
		$this->options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
	}

	/**
	 * Get the global Captcha instance for a specific action.
	 *
	 * If a specific Captcha is not defined in $wgCaptchaTriggers[$action]['class'],
	 * $wgCaptchaClass will be returned instead.
	 *
	 * @stable to call - May be used by code not visible in codesearch
	 * @since 1.47
	 */
	public function getGlobalInstance( string $action = '' ): SimpleCaptcha {
		$captchaTriggers = $this->options->get( 'CaptchaTriggers' );
		$defaultCaptchaClass = $this->options->get( 'CaptchaClass' );

		// Use the CaptchaTriggers class for this action, falling back to the default class if not set
		$class = $captchaTriggers[$action]['class'] ?? null;
		if ( $action === CaptchaTriggers::BAD_LOGIN_PER_USER && $class === null ) {
			$class = $captchaTriggers[CaptchaTriggers::BAD_LOGIN]['class'] ?? null;
		}
		$class ??= $defaultCaptchaClass;

		$hookRunner = new HookRunner( $this->hookContainer );
		// Allow hook implementers to override the class that's about to be cached.
		$hookRunner->onConfirmEditCaptchaClass( $action, $class );

		if ( !isset( static::$globalInstances[$action][$class] ) ) {
			// There is not a cached instance, construct a new one based on the mapping
			/** @var SimpleCaptcha $classInstance */
			$captchaClassName = self::CAPTCHA_NAME_TO_CLASS[$class] ??
				self::CAPTCHA_NAME_TO_CLASS[$defaultCaptchaClass] ??
				$defaultCaptchaClass;
			$classInstance = new $captchaClassName;
			$classInstance->setConfig( $captchaTriggers[$action]['config'] ?? [] );
			static::$globalInstances[$action][$class] = $classInstance;
		}

		return static::$globalInstances[$action][$class];
	}

	/**
	 * Gets the global CAPTCHA instance that applies for the given {@link IContextSource}.
	 *
	 * @stable to call
	 * @since 1.47
	 */
	public function getGlobalInstanceFromContext( IContextSource $context ): SimpleCaptcha {
		if (
			$context->getTitle()->isSpecial( 'CreateAccount' ) ||
			$context->getActionName() === 'createaccount'
		) {
			$action = CaptchaTriggers::CREATE_ACCOUNT;
		} elseif (
			$context->getTitle()->isSpecial( 'Userlogin' ) ||
			in_array( $context->getActionName(), [ 'login', 'clientlogin' ] )
		) {
			$session = $context->getRequest()->getSession();
			$suggestedUsername = $session->suggestLoginUsername();
			$loginAttemptCounter = $this->loginAttemptCounterFactory->newLoginAttemptCounter(
				$this->getGlobalInstance( CaptchaTriggers::BAD_LOGIN )
			);

			if (
				$session->get( 'ConfirmEdit:loginCaptchaPerUserTriggered' ) ||
				( $suggestedUsername && $loginAttemptCounter->isBadLoginPerUserTriggered( $suggestedUsername ) )
			) {
				$action = CaptchaTriggers::BAD_LOGIN_PER_USER;
			} elseif ( $loginAttemptCounter->isBadLoginTriggered() ) {
				$action = CaptchaTriggers::BAD_LOGIN;
			} else {
				$action = CaptchaTriggers::LOGIN_ATTEMPT;
			}
		} elseif (
			$context->getTitle()->isSpecial( 'Emailuser' ) ||
			$context->getActionName() === 'emailuser'
		) {
			$action = CaptchaTriggers::SENDEMAIL;
		} elseif ( $context->getTitle()->exists() ) {
			$action = CaptchaTriggers::EDIT;
		} else {
			$action = CaptchaTriggers::CREATE;
		}

		return $this->getGlobalInstance( $action );
	}

	/**
	 * Clears the global Captcha cache for testing
	 *
	 * @codeCoverageIgnore
	 * @internal Only for use in PHPUnit tests.
	 */
	public function unsetGlobalInstancesForTests(): void {
		if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
			throw new BadMethodCallException( 'Cannot unset ' . __CLASS__ . ' instance in operation.' );
		}
		static::$globalInstances = [];
	}
}
