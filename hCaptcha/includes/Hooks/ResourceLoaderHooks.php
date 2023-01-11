<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\hCaptcha\Hooks;

use Config;
use ResourceLoaderContext;

class ResourceLoaderHooks {
	/**
	 * Passes config variables to ext.confirmEdit.hCaptcha.visualEditor ResourceLoader module.
	 * @param ResourceLoaderContext $context
	 * @param Config $config
	 * @return array
	 */
	public static function getHCaptchaResourceLoaderConfig(
		ResourceLoaderContext $context,
		Config $config
	) {
		return [
			'hCaptchaSiteKey' => $config->get( 'HCaptchaSiteKey' ),
			'hCaptchaScriptURL' => 'https://js.hcaptcha.com/1/api.js',
		];
	}
}
