<?php

namespace MediaWiki\Extension\ConfirmEdit\Turnstile;

use MediaWiki\Config\Config;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderGetConfigVarsHook;

class Hooks implements ResourceLoaderGetConfigVarsHook {
	/**
	 * Adds extra variables to the global config
	 *
	 * @param array &$vars Global variables object
	 * @param string $skin
	 * @param Config $config
	 */
	public function onResourceLoaderGetConfigVars( array &$vars, $skin, Config $config ): void {
		if ( $config->get( 'CaptchaClass' ) === Turnstile::class ) {
			$vars['wgConfirmEditConfig'] = [
				'turnstileSiteKey' => $config->get( 'TurnstileSiteKey' ),
				'turnstileScriptURL' => 'https://challenges.cloudflare.com/turnstile/v0/api.js'
			];
		}
	}
}
