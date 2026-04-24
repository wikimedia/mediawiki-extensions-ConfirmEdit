<?php

namespace MediaWiki\Extension\ConfirmEdit\Hooks\Handlers;

use MediaWiki\Extension\ConfirmEdit\Services\LoadedCaptchasProvider;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\ResourceLoader\CodexModule;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderRegisterModulesHook;
use MediaWiki\ResourceLoader\ResourceLoader;

class RLRegisterModulesHandler implements ResourceLoaderRegisterModulesHook {

	public function __construct(
		private readonly LoadedCaptchasProvider $loadedCaptchasProvider,
		private readonly ExtensionRegistry $extensionRegistry,
	) {
	}

	/**
	 * Registers ResourceLoader modules based on the captchas loaded as determined
	 * by {@link LoadedCaptchasProvider}.
	 *
	 * @inheritDoc
	 */
	public function onResourceLoaderRegisterModules( ResourceLoader $rl ): void {
		$dir = dirname( __DIR__, 3 ) . '/resources/';
		$modules = [];

		// Only load the captcha-specific resource loader modules / messages if that captcha is loaded
		$loadedCaptchas = $this->loadedCaptchasProvider->getLoadedCaptchas();

		$captchaModuleMessages = [
			'colon-separator',
			'captcha-edit',
			'captcha-label'
		];

		// ExtensionRegistry::isLoaded checks are only needed because ::getLoadedCaptchas returns
		// all captchas for testing but the i18n isn't defined during tests. Therefore, the
		// ResourcesTest::testMissingMessages test will fail without this check
		if (
			in_array( 'QuestyCaptcha', $loadedCaptchas, true ) &&
			$this->extensionRegistry->isLoaded( 'QuestyCaptcha' )
		) {
			$captchaModuleMessages[] = 'questycaptcha-edit';
		}

		if (
			in_array( 'FancyCaptcha', $loadedCaptchas, true ) &&
			$this->extensionRegistry->isLoaded( 'FancyCaptcha' )
		) {
			$captchaModuleMessages[] = 'fancycaptcha-edit';
			$captchaModuleMessages[] = 'fancycaptcha-reload-text';
			$captchaModuleMessages[] = 'fancycaptcha-imgcaptcha-ph';
		}

		$modules['ext.confirmEdit.CaptchaInputWidget'] = [
			'localBasePath' => $dir,
			'remoteExtPath' => 'ConfirmEdit/resources',
			'scripts' => 'libs/ext.confirmEdit.CaptchaInputWidget.js',
			'styles' => 'libs/ext.confirmEdit.CaptchaInputWidget.less',
			'messages' => $captchaModuleMessages,
			'dependencies' => 'oojs-ui-core',
		];
		$modules['ext.confirmEdit.CaptchaWidget'] = [
			'localBasePath' => $dir,
			'remoteExtPath' => 'ConfirmEdit/resources',
			'scripts' => 'libs/ext.confirmEdit.CaptchaWidget.js',
			'styles' => 'libs/ext.confirmEdit.CaptchaWidget.less',
			'messages' => $captchaModuleMessages,
		];

		// Some CAPTCHAs need an input field, while others render their own interface and so don't need Codex
		if ( array_intersect( [ 'QuestyCaptcha', 'SimpleCaptcha' ], $loadedCaptchas ) ) {
			$modules['ext.confirmEdit.CaptchaWidget']['class'] = CodexModule::class;
			$modules['ext.confirmEdit.CaptchaWidget']['codexStyleOnly'] = true;
			$modules['ext.confirmEdit.CaptchaWidget']['codexComponents'] = [ 'CdxTextInput' ];
		}

		if ( in_array( 'HCaptcha', $loadedCaptchas, true ) ) {
			$modules['ext.confirmEdit.hCaptcha'] = [
				'localBasePath' => $dir,
				'remoteExtPath' => 'ConfirmEdit/resources',
				'packageFiles' => [
					'ext.confirmEdit.hCaptcha/init.js',
					'ext.confirmEdit.hCaptcha/secureEnclave.js',
					'ext.confirmEdit.hCaptcha/utils.js',
					'ext.confirmEdit.hCaptcha/ProgressIndicatorWidget.js',
					'ext.confirmEdit.hCaptcha/ErrorWidget.js',
					'ext.confirmEdit.hCaptcha/mobileFrontend/initMobileFrontend.js',
					'ext.confirmEdit.hCaptcha/mobileFrontend/mobileFrontendSecureEnclave.js',
					'ext.confirmEdit.hCaptcha/ve/initPlugins.js',
					'ext.confirmEdit.hCaptcha/ve/ve.init.mw.HCaptchaSaveErrorHandler.js',
					'ext.confirmEdit.hCaptcha/ve/ve.init.mw.HCaptchaOnLoadHandler.js',
					'ext.confirmEdit.hCaptcha/ve/ve.init.mw.HCaptcha.js',
					[
						'name' => 'ext.confirmEdit.hCaptcha/config.json',
						'config' => [
							'HCaptchaApiUrl',
							'HCaptchaSiteKey',
							'HCaptchaEnterprise',
							'HCaptchaSecureEnclave',
							'HCaptchaApiUrlIntegrityHash',
							'HCaptchaEnabledInMobileFrontend',
							'HCaptchaInvisibleMode',
						]
					],
				],
				'styles' => [
					'ext.confirmEdit.hCaptcha/ext.confirmEdit.hCaptcha.less',
				],
				'messages' => [
					'hcaptcha-challenge-closed',
					'hcaptcha-challenge-expired',
					'hcaptcha-generic-error',
					'hcaptcha-internal-error',
					'hcaptcha-network-error',
					'hcaptcha-rate-limited',
					'hcaptcha-loading-indicator-label',
					'hcaptcha-privacy-policy',
					'hcaptcha-visual-editor-error-handler-warning',
				],
				'dependencies' => [
					'oojs-ui',
					'web2017-polyfills',
					'codex-styles',
				],
			];
			$modules['ext.confirmEdit.hCaptcha.styles'] = [
				'localBasePath' => $dir,
				'remoteExtPath' => 'ConfirmEdit/resources',
				'styles' => [
					'ext.confirmEdit.hCaptcha.styles/ext.confirmEdit.hCaptcha.styles.less',
				],
			];
		}

		$rl->register( $modules );
	}

}
