<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Auth;

use MediaWiki\Auth\AbstractPreAuthenticationProvider;
use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Auth\AuthenticationResponse;
use MediaWiki\Auth\AuthManager;
use MediaWiki\Extension\ConfirmEdit\CaptchaTriggers;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\HCaptcha;
use MediaWiki\Extension\ConfirmEdit\Services\CaptchaFactory;
use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Status\Status;
use MediaWiki\User\User;

class CaptchaPreAuthenticationProvider extends AbstractPreAuthenticationProvider {

	public function __construct(
		private readonly LoginAttemptCounterFactory $loginAttemptCounterFactory,
		private readonly CaptchaFactory $captchaFactory,
	) {
	}

	/** @inheritDoc */
	public function getAuthenticationRequests( $action, array $options ) {
		$user = User::newFromName( $options['username'] );

		$logger = LoggerFactory::getInstance( 'captcha' );
		$needed = false;
		$captcha = null;
		switch ( $action ) {
			case AuthManager::ACTION_CREATE:
				$u = $user ?: new User();
				$captcha = $this->captchaFactory->getGlobalInstance( CaptchaTriggers::CREATE_ACCOUNT );
				$needed = $captcha->needCreateAccountCaptcha( $u );
				if ( $needed ) {
					$captcha->setAction( CaptchaTriggers::CREATE_ACCOUNT );
					// This is debug level simply because generally a
					// captcha is either always or never triggered on
					// view of Special:CreateAccount, so it gets pretty noisy
					$logger->debug( 'Captcha shown on account creation for {user}', [
						'event' => 'captcha.display',
						'eventType' => 'accountcreation',
						'user' => $u->getName()
					] );
				}
				break;
			case AuthManager::ACTION_LOGIN:
				// Captcha is shown on login when there were too many failed attempts from the current IP
				// or using a given username, or if a hook handler says that a CAPTCHA should be shown.
				// The varying on username is a bit awkward because we don't know the
				// username yet. The username from the last successful login is stored in a cookie.
				// We still must make sure to not lock out other usernames, so after the first
				// failed login attempt using a username that needs a captcha, set a session flag
				// to display a captcha on login from that point on. This will result in confusing
				// error messages if the browser cannot persist the session (because we'll never show
				// a required captcha field), but then login would be impossible anyway, so no big deal.

				// If the username ends up to be one that does not trigger the captcha, that will
				// result in weird behavior (if the user leaves the captcha field empty, they get
				// a required field error; if they fill it with an invalid answer, it will pass)
				// - again, not a huge deal.
				$captcha = $this->captchaFactory->getGlobalInstance( CaptchaTriggers::BAD_LOGIN );
				$session = $this->manager->getRequest()->getSession();
				$suggestedUsername = $session->suggestLoginUsername();
				$loginCounter = $this->loginAttemptCounterFactory->newLoginAttemptCounter( $captcha );

				$userProbablyNeedsCaptcha = $session->get( 'ConfirmEdit:loginCaptchaPerUserTriggered' );
				if (
					$userProbablyNeedsCaptcha
					|| $loginCounter->isBadLoginTriggered()
					|| ( $suggestedUsername && $loginCounter->isBadLoginPerUserTriggered( $suggestedUsername ) )
				) {
					$captcha->setAction( CaptchaTriggers::BAD_LOGIN );
					$logger->info( 'Captcha shown on login by {clientip} for {suggestedUser}', [
						'event' => 'captcha.display',
						'eventType' => 'badlogin',
						'suggestedUser' => $suggestedUsername,
						'clientip' => $this->manager->getRequest()->getIP(),
						'ua' => $this->manager->getRequest()->getHeader( 'User-Agent' )
					] );
					$needed = true;
					break;
				}

				$captcha = $this->captchaFactory->getGlobalInstance( CaptchaTriggers::LOGIN_ATTEMPT );
				if ( $captcha->triggersCaptcha( CaptchaTriggers::LOGIN_ATTEMPT ) ) {
					$captcha->setAction( CaptchaTriggers::LOGIN_ATTEMPT );
					$logger->info( 'Captcha shown on login attempt by {clientip} for {suggestedUser}', [
						'event' => 'captcha.display',
						'eventType' => 'loginattempt',
						'suggestedUser' => $suggestedUsername,
						'clientip' => $this->manager->getRequest()->getIP(),
						'ua' => $this->manager->getRequest()->getHeader( 'User-Agent' )
					] );
					$needed = true;
					break;
				}
				break;
		}

		// Return the CaptchaAuthenticationRequest instance if a captcha is needed and defined.
		if ( $needed && $captcha ) {
			return [ $captcha->createAuthenticationRequest() ];
		} else {
			return [];
		}
	}

	/** @inheritDoc */
	public function testForAuthentication( array $reqs ) {
		$captcha = $this->captchaFactory->getGlobalInstance( CaptchaTriggers::CREATE_ACCOUNT );
		$username = AuthenticationRequest::getUsernameFromRequests( $reqs );
		$loginCounter = $this->loginAttemptCounterFactory->newLoginAttemptCounter( $captcha );
		$success = true;
		$isBadLoginPerUserTriggered = $username && $loginCounter->isBadLoginPerUserTriggered( $username );
		$isBadLoginTriggered = $isBadLoginPerUserTriggered || $loginCounter->isBadLoginTriggered();
		$loginTriggersCaptcha = $captcha->triggersCaptcha( CaptchaTriggers::LOGIN_ATTEMPT );
		$captchaRequired = $isBadLoginTriggered || $loginTriggersCaptcha;

		$captchaRequest = AuthenticationRequest::getRequestByClass(
			$reqs, CaptchaAuthenticationRequest::class, true
		);
		$captchaSubmitted = $captchaRequest !== null
			&& (string)( $captchaRequest->captchaWord ?? '' ) !== '';

		if ( $captchaRequired ) {
			if ( $isBadLoginTriggered ) {
				$captcha = $this->captchaFactory->getGlobalInstance( CaptchaTriggers::BAD_LOGIN );
				$captcha->setAction( CaptchaTriggers::BAD_LOGIN );
				$captcha->setTrigger( "post-badlogin login '$username'" );
			} else {
				$captcha = $this->captchaFactory->getGlobalInstance( CaptchaTriggers::LOGIN_ATTEMPT );
				$captcha->setAction( CaptchaTriggers::LOGIN_ATTEMPT );
				$captcha->setTrigger( "loginattempt login '$username'" );
			}
			$success = $captchaSubmitted && $this->verifyCaptcha( $captcha, $reqs, new User() );
			$action = $isBadLoginTriggered ? 'bad login' : 'login page';
			LoggerFactory::getInstance( 'captcha' )->info( "Captcha shown on $action for {user}", [
				'event' => 'captcha.submit',
				'eventType' => $isBadLoginTriggered ? 'badlogin' : 'loginattempt',
				'successful' => $success,
				'user' => $username ?? 'unknown',
				'clientip' => $this->manager->getRequest()->getIP(),
				'ua' => $this->manager->getRequest()->getHeader( 'User-Agent' )
			] );
		}

		if ( $isBadLoginPerUserTriggered ) {
			$session = $this->manager->getRequest()->getSession();
			// A captcha is needed to log in with this username, so display it on the next attempt.
			$session->set( 'ConfirmEdit:loginCaptchaPerUserTriggered', true );
		}

		if ( $success ) {
			return Status::newGood();
		}

		if ( $captchaRequired && !$captchaSubmitted ) {
			return $this->makeError( 'captcha-login-required', $captcha );
		}

		// Make brute force attacks harder by not telling whether the password or the
		// captcha failed.
		return $this->makeError( 'wrongpassword', $captcha );
	}

	/** @inheritDoc */
	public function testForAccountCreation( $user, $creator, array $reqs ) {
		$captcha = $this->captchaFactory->getGlobalInstance( CaptchaTriggers::CREATE_ACCOUNT );

		if ( $captcha->needCreateAccountCaptcha( $creator ) ) {
			$username = $user->getName();
			$captcha->setAction( CaptchaTriggers::CREATE_ACCOUNT );
			$captcha->setTrigger( "new account '$username'" );
			$success = $this->verifyCaptcha( $captcha, $reqs, $user );
			$ip = $this->manager->getRequest()->getIP();
			LoggerFactory::getInstance( 'captcha' )->info(
				'Captcha submitted on account creation for {user}', [
					'event' => 'captcha.submit',
					'eventType' => 'accountcreation',
					'successful' => $success,
					'user' => $username,
					'clientip' => $ip,
					'ua' => $this->manager->getRequest()->getHeader( 'User-Agent' )
				]
			);
			if ( !$success ) {
				return $this->makeError( 'captcha-createaccount-fail', $captcha );
			}
		}
		return Status::newGood();
	}

	/** @inheritDoc */
	public function postAuthentication( $user, AuthenticationResponse $response ) {
		$captcha = $this->captchaFactory->getGlobalInstance( CaptchaTriggers::BAD_LOGIN );
		$loginCounter = $this->loginAttemptCounterFactory->newLoginAttemptCounter( $captcha );
		switch ( $response->status ) {
			case AuthenticationResponse::PASS:
			case AuthenticationResponse::RESTART:
				$this->manager->getRequest()->getSession()->remove( 'ConfirmEdit:loginCaptchaPerUserTriggered' );
				$loginCounter->resetBadLoginCounter( $user ? $user->getName() : null );
				break;
			case AuthenticationResponse::FAIL:
				// If authentication failed, the user will see the login form again and so we want a CAPTCHA to show
				// on this form if it's still required.
				$captcha->clearCaptchaSolved();
				$this->captchaFactory->getGlobalInstance( CaptchaTriggers::BAD_LOGIN_PER_USER )->clearCaptchaSolved();
				$this->captchaFactory->getGlobalInstance( CaptchaTriggers::LOGIN_ATTEMPT )->clearCaptchaSolved();

				$loginCounter->increaseBadLoginCounter( $user ? $user->getName() : null );
				break;
		}
	}

	/** @inheritDoc */
	public function postAccountCreation( $user, $creator, AuthenticationResponse $response ) {
		if ( $response->status !== AuthenticationResponse::FAIL ) {
			return;
		}

		// If account creation failed, the user will see the form again and so we want a CAPTCHA to show
		// on this form if it's still required.
		$this->captchaFactory->getGlobalInstance( CaptchaTriggers::CREATE_ACCOUNT )->clearCaptchaSolved();
	}

	/**
	 * Verify submitted captcha.
	 * Assumes that the user has to pass the captcha (permission checks are caller's responsibility).
	 * @param SimpleCaptcha $captcha
	 * @param AuthenticationRequest[] $reqs
	 * @param User $user
	 * @return bool
	 */
	protected function verifyCaptcha( SimpleCaptcha $captcha, array $reqs, User $user ) {
		/** @var CaptchaAuthenticationRequest $req */
		$req = AuthenticationRequest::getRequestByClass(
			$reqs,
			CaptchaAuthenticationRequest::class,
			true
		);
		if ( !$req ) {
			return false;
		}
		return $captcha->passCaptchaLimited( $req->captchaId, $req->captchaWord, $user );
	}

	/**
	 * @param string $message Message key
	 * @param SimpleCaptcha $captcha
	 * @return Status
	 */
	protected function makeError( $message, SimpleCaptcha $captcha ) {
		$error = $captcha->getError();
		if ( $error ) {
			if ( $captcha instanceof HCaptcha ) {
				return Status::newFatal( wfMessage( 'hcaptcha-internal-error' ) );
			}

			return Status::newFatal( wfMessage( 'captcha-error', $error ) );
		}
		return Status::newFatal( $message );
	}
}
