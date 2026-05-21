<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration;

use MediaWiki\Extension\ConfirmEdit\Services\CaptchaFactory;
use MediaWiki\MediaWikiServices;

/**
 * Helper trait for clearing the global CAPTCHA instances stored in the {@link CaptchaFactory} service.
 *
 * @since 1.47
 */
trait CaptchaTestHelperTrait {

	/**
	 * Calls {@link CaptchaFactory::unsetGlobalInstancesForTests}
	 *
	 * @since 1.47
	 * @stable to call
	 */
	protected static function clearCaptchaFactoryGlobalInstances(): void {
		/** @var CaptchaFactory $captchaFactory */
		$captchaFactory = MediaWikiServices::getInstance()->get( 'ConfirmEditCaptchaFactory' );
		$captchaFactory->unsetGlobalInstancesForTests();
	}
}
