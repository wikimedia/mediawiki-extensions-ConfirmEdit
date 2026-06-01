<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Hooks\Handlers;

use MediaWiki\Config\Config;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\GradeCBundleModule;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\HCaptchaOutput;
use MediaWiki\Extension\ConfirmEdit\Services\LoadedCaptchasProvider;
use MediaWiki\MediaWikiServices;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\ResourceLoader\CodexModule;
use MediaWiki\ResourceLoader\Context;
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

		if ( in_array( 'HCaptcha', $loadedCaptchas, true ) ) {
			$captchaModuleMessages[] = 'hcaptcha-force-show-captcha-edit';
		}

		$modules['ext.confirmEdit.CaptchaWidget'] = [
			'localBasePath' => $dir,
			'remoteExtPath' => 'ConfirmEdit/resources',
			'scripts' => 'libs/ext.confirmEdit.CaptchaWidget.js',
			'styles' => 'libs/ext.confirmEdit.CaptchaWidget.less',
			'messages' => $captchaModuleMessages,
		];

		// Some CAPTCHAs need an input field, while others render their own interface and so don't need Codex
		if ( array_intersect( [ 'QuestyCaptcha', 'SimpleCaptcha', 'FancyCaptcha' ], $loadedCaptchas ) ) {
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
					'ext.confirmEdit.hCaptcha/theme.js',
					'ext.confirmEdit.hCaptcha/ProgressIndicatorWidget.js',
					'ext.confirmEdit.hCaptcha/RiskScoreCollector.js',
					'ext.confirmEdit.hCaptcha/ErrorWidget.js',
					'ext.confirmEdit.hCaptcha/mobileFrontend/initMobileFrontend.js',
					'ext.confirmEdit.hCaptcha/mobileFrontend/mobileFrontendSecureEnclave.js',
					'ext.confirmEdit.hCaptcha/ve/initPlugins.js',
					'ext.confirmEdit.hCaptcha/ve/ve.init.mw.HCaptchaSaveErrorHandler.js',
					'ext.confirmEdit.hCaptcha/ve/ve.init.mw.HCaptchaOnLoadHandler.js',
					'ext.confirmEdit.hCaptcha/ve/ve.init.mw.HCaptchaCollectRiskScore.js',
					'ext.confirmEdit.hCaptcha/ve/ve.init.mw.HCaptcha.js',
					[
						'name' => 'ext.confirmEdit.hCaptcha/config.json',
						'callback' => self::class . '::getHCaptchaConfig',
					],
				],
				'styles' => [
					'ext.confirmEdit.hCaptcha/ext.confirmEdit.hCaptcha.less',
				],
				'messages' => array_merge( HCaptchaOutput::RUNTIME_MESSAGE_KEYS, [
					'hcaptcha-privacy-policy',
					'hcaptcha-visual-editor-error-handler-warning',
					'hcaptcha-force-show-captcha-edit',
				] ),
				'dependencies' => [
					'mediawiki.api',
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
			$modules['ext.confirmEdit.hCaptcha.gradeC'] = [
				'class' => GradeCBundleModule::class,
			];
		}

		$rl->register( $modules );
	}

	/**
	 * Fetches the hCaptcha config for use in the ext.confirmEdit.hCaptcha module as the config.json file
	 */
	public static function getHCaptchaConfig( Context $context, Config $config ): array {
		// We don't inject this in the constructor because it creates a circular dependency
		/** @var HCaptchaOutput $hCaptchaOutput */
		$hCaptchaOutput = MediaWikiServices::getInstance()->get( 'HCaptchaOutput' );
		return [
			'HCaptchaSiteKey' => $config->get( 'HCaptchaSiteKey' ),
			'HCaptchaEnterprise' => $config->get( 'HCaptchaEnterprise' ),
			'HCaptchaCustomThemeSupported' => $config->get( 'HCaptchaCustomThemeSupported' ),
			'HCaptchaSecureEnclave' => $config->get( 'HCaptchaSecureEnclave' ),
			'HCaptchaApiUrlIntegrityHash' => $config->get( 'HCaptchaApiUrlIntegrityHash' ),
			'HCaptchaEnabledInMobileFrontend' => $config->get( 'HCaptchaEnabledInMobileFrontend' ),
			'HCaptchaInvisibleMode' => $config->get( 'HCaptchaInvisibleMode' ),
			'HCaptchaApiUrl' => $hCaptchaOutput->getHCaptchaApiUrl( $context->getLanguage() ),
		];
	}
}
