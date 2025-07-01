<?php

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration;

trait MockHCaptchaConfigTrait {
	protected function setUp(): void {
		parent::setUp();

		// Set hCaptcha extension config to default values as the config won't be loaded if the hCaptcha extension
		// is not loaded (this happens when the tests are run on a wiki where a different captcha is being used).
		$this->overrideConfigValues( [
			'HCaptchaProxy' => false,
			'HCaptchaSiteKey' => '',
			'HCaptchaSecretKey' => '',
			'HCaptchaSendRemoteIP' => false,
			'HCaptchaApiUrl' => 'https://js.hcaptcha.com/1/api.js',
			'HCaptchaVerifyUrl' => 'https://api.hcaptcha.com/siteverify',
			'HCaptchaEnterprise' => false,
			'HCaptchaPassiveMode' => false,
			'HCaptchaCSPRules' => [
				'https://hcaptcha.com',
				'https://*.hcaptcha.com',
			],
			'HCaptchaSecureEnclave' => false,
			'HCaptchaDeveloperMode' => false,
			'HCaptchaUseRiskScore' => false,
		] );
	}
}
