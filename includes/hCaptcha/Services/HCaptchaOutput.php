<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\hCaptcha\Services;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\HCaptcha;
use MediaWiki\Extension\ConfirmEdit\Services\CaptchaFactory;
use MediaWiki\Html\Html;
use MediaWiki\Json\FormatJson;
use MediaWiki\Language\LanguageFallback;
use MediaWiki\Language\LanguageFallbackMode;
use MediaWiki\MainConfigNames;
use MediaWiki\Output\OutputPage;
use MediaWiki\ResourceLoader\ClientHtml;
use MediaWiki\ResourceLoader\Context;
use MediaWiki\ResourceLoader\ResourceLoader;
use Wikimedia\Assert\Assert;

/**
 * Service used to de-duplicate the code for adding hCaptcha to the output in both
 * {@link HCaptcha} and {@link HTMLHCaptchaField}.
 */
class HCaptchaOutput {

	/** The `mwclientpreferences` cookie token set when the night-mode client preference is "night". */
	private const NIGHT_MODE_CLIENT_PREF = 'skin-theme-clientpref-night';

	/** @internal Only public for service wiring use. */
	public const CONSTRUCTOR_OPTIONS = [
		'HCaptchaSiteKey',
		'HCaptchaEnterprise',
		'HCaptchaSecureEnclave',
		'HCaptchaInvisibleMode',
		'HCaptchaApiUrl',
		'HCaptchaApiUrlIntegrityHash',
		MainConfigNames::LoadScript,
	];

	/**
	 * i18n keys required by the runtime hCaptcha JS (showError, loading
	 * indicator, etc). Shared with the canonical Grade A module via
	 * RLRegisterModulesHandler so changes stay in sync.
	 */
	public const RUNTIME_MESSAGE_KEYS = [
		'hcaptcha-challenge-closed',
		'hcaptcha-challenge-expired',
		'hcaptcha-generic-error',
		'hcaptcha-internal-error',
		'hcaptcha-network-error',
		'hcaptcha-rate-limited',
		'hcaptcha-loading-indicator-label',
	];

	/**
	 * Languages that hCaptcha and MediaWiki both support, used to show any hCaptcha UI
	 * in the same language as the rest of the MediaWiki UI.
	 *
	 * Languages that hCaptcha support are listed at https://docs.hcaptcha.com/languages/
	 */
	public const LANGUAGES_SUPPORTED_BY_HCAPTCHA = [
		'af', 'am', 'ar', 'az', 'be', 'bg', 'bn', 'bs', 'ca', 'ceb', 'co', 'cs', 'cy',
		'da', 'de', 'el', 'en', 'eo', 'es', 'et', 'eu', 'fa', 'fi', 'fr', 'fy', 'ga',
		'gd', 'gl', 'gu', 'ha', 'haw', 'he', 'hi', 'hmn', 'hr', 'ht', 'hu', 'hy',
		'id', 'ig', 'is', 'it', 'ja', 'jw', 'ka', 'kk', 'km', 'kn', 'ko', 'ku', 'ky',
		'la', 'lb', 'lo', 'lt', 'lv', 'me', 'mg', 'mi', 'mk', 'ml', 'mn', 'mr', 'ms',
		'mt', 'my', 'ne', 'nl', 'no', 'ny', 'or', 'pa', 'pl', 'ps', 'pt', 'ro', 'ru',
		'rw', 'sd', 'si', 'sk', 'sl', 'sm', 'sn', 'so', 'sq', 'sr', 'st', 'su', 'sv',
		'sw', 'ta', 'te', 'tg', 'th', 'tk', 'tl', 'tr', 'tt', 'ug', 'uk', 'ur', 'uz',
		'vi', 'xh', 'yi', 'yo', 'zh', 'zu',
	];

	public function __construct(
		private readonly ServiceOptions $options,
		private readonly ResourceLoader $resourceLoader,
		private readonly LanguageFallback $languageFallback,
		private readonly CaptchaFactory $captchaFactory,
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
	}

	/**
	 * Returns the HTML needed to add HCaptcha to a form.
	 *
	 * This method also adds other required script tags and ResourceLoader modules to the provided OutputPage.
	 *
	 * @param OutputPage $outputPage
	 * @param bool $previouslyFailedHCaptcha Whether the user has failed HCaptcha for a previous attempt at
	 *   submitting the associated form.
	 * @return string HTML of the hCaptcha fields to append to the outputted HTML
	 */
	public function addHCaptchaToForm( OutputPage $outputPage, bool $previouslyFailedHCaptcha ): string {
		/** @var HCaptcha $simpleCaptcha */
		$simpleCaptcha = $this->captchaFactory->getGlobalInstanceFromContext( $outputPage );
		Assert::postcondition(
			$simpleCaptcha instanceof HCaptcha, '$simpleCaptcha is not an instance of HCaptcha'
		);
		$siteKey = $simpleCaptcha->getSiteKeyForAction();
		$useInvisibleMode = $this->options->get( 'HCaptchaInvisibleMode' );

		$hCaptchaElementAttribs = [
			'id' => 'h-captcha',
			'class' => [
				'h-captcha',
				'mw-confirmedit-captcha-fail' => $previouslyFailedHCaptcha,
			],
			'data-sitekey' => $siteKey,
		];
		// `data-theme="dark"` selects hCaptcha's built-in dark theme for the auto-render
		// path (non-secure-enclave), where the SDK renders the widget from these data
		// attributes. On the secure-enclave path the SDK is loaded with render=explicit and
		// never auto-renders, so the client passes the dark theme object to hcaptcha.render()
		// instead (see resources/.../theme.js) and this attribute is ignored there.
		if ( $this->isDarkMode( $outputPage ) ) {
			$hCaptchaElementAttribs['data-theme'] = 'dark';
		}
		if ( $useInvisibleMode ) {
			$hCaptchaElementAttribs['data-size'] = 'invisible';
		}
		$output = Html::element( 'div', $hCaptchaElementAttribs );

		$useSecureEnclave = $this->options->get( 'HCaptchaEnterprise' ) &&
			$this->options->get( 'HCaptchaSecureEnclave' );
		if ( !$useSecureEnclave ) {
			$hCaptchaApiUrl = $this->getHCaptchaApiUrl( $outputPage->getLanguage()->getCode() );
			$outputPage->addHeadItem(
				'h-captcha',
				Html::element( 'script', [ 'src' => $hCaptchaApiUrl, 'async', 'defer' ] )
			);
		}

		// Load the hCaptcha module if we are adding the hCaptcha field. This will handle the secure enclave mode if
		// it is enabled.
		$outputPage->addModules( 'ext.confirmEdit.hCaptcha' );
		$outputPage->addModuleStyles( 'ext.confirmEdit.hCaptcha.styles' );

		if ( $useSecureEnclave && $this->shouldEmitGradeCBootstrap( $outputPage ) ) {
			$this->addGradeCBootstrap( $outputPage, $siteKey );
		}

		if ( $useInvisibleMode ) {
			$output .= Html::rawElement(
				'div',
				[ 'class' => 'h-captcha-privacy-policy' ],
				$outputPage->msg( 'hcaptcha-privacy-policy' )->parse()
			);
		}

		// Add noscript message for users with JavaScript disabled in edit page
		$output .= Html::rawElement(
			'noscript',
			[ 'class' => 'h-captcha-noscript-container' ],
			Html::rawElement(
				'div',
				[ 'class' => 'h-captcha-noscript-message cdx-message cdx-message--error' ],
				$outputPage->msg( 'hcaptcha-noscript' )->parse()
			)
		);
		if ( $simpleCaptcha->shouldForceShowCaptcha() ) {
			// Set a flag that can be used in HCaptcha::shouldCheck() to know if the "showcaptcha"
			// AbuseFilter consequence was invoked.
			$output .= Html::hidden( 'wgConfirmEditForceShowCaptcha', true );
		}
		return $output;
	}

	/**
	 * Night-mode detection from the `mwclientpreferences` cookie for the data-theme attribute.
	 * "os" can't be resolved server-side (treated as not-dark); the client render path covers it on Grade A.
	 */
	private function isDarkMode( OutputPage $outputPage ): bool {
		$clientPrefs = $outputPage->getRequest()->getCookie( ClientHtml::CLIENT_PREFS_COOKIE_NAME );
		if ( $clientPrefs === null ) {
			return false;
		}
		return in_array( self::NIGHT_MODE_CLIENT_PREF, explode( ',', $clientPrefs ), true );
	}

	/**
	 * Gets the hCaptcha API url, preferrably setting the display language for hCaptcha to
	 * match the language that the rest of the MediaWiki page is displayed in.
	 */
	public function getHCaptchaApiUrl( string $languageCode ): string {
		$hCaptchaApiUrl = $this->options->get( 'HCaptchaApiUrl' );

		$languageParam = null;
		if ( in_array( $languageCode, self::LANGUAGES_SUPPORTED_BY_HCAPTCHA, true ) ) {
			$languageParam = $languageCode;
		} else {
			$fallbackLanguages = $this->languageFallback->getAll(
				$languageCode,
				LanguageFallbackMode::STRICT
			);
			foreach ( $fallbackLanguages as $fallbackLanguage ) {
				if ( in_array( $fallbackLanguage, self::LANGUAGES_SUPPORTED_BY_HCAPTCHA, true ) ) {
					$languageParam = $fallbackLanguage;
					break;
				}
			}
		}

		if ( $languageParam ) {
			$hCaptchaApiUrl = wfAppendQuery( $hCaptchaApiUrl, [ 'hl' => $languageParam ] );
		}

		return $hCaptchaApiUrl;
	}

	/**
	 * Grade C clients are supported for:
	 * - desktop wikitext editor via ?action=edit|submit
	 * - Special:CreateAccount
	 * - Special:UserLogin
	 */
	private function shouldEmitGradeCBootstrap( OutputPage $outputPage ): bool {
		$title = $outputPage->getTitle();
		if ( $title !== null && ( $title->isSpecial( 'CreateAccount' ) || $title->isSpecial( 'Userlogin' ) ) ) {
			return true;
		}
		$action = $outputPage->getActionName();
		return $action === 'edit' || $action === 'submit';
	}

	/**
	 * Emit the inline bootstrap that loads the Grade C hCaptcha bundle via NORLQ.
	 */
	private function addGradeCBootstrap(
		OutputPage $outputPage,
		string $siteKey
	): void {
		$module = $this->resourceLoader->getModule( 'ext.confirmEdit.hCaptcha.gradeC' );
		$context = new Context( $this->resourceLoader, $outputPage->getRequest() );
		$version = $module->getVersionHash( $context );
		$bundleUrl = wfAppendQuery(
			$this->options->get( MainConfigNames::LoadScript ),
			[
				'modules' => 'ext.confirmEdit.hCaptcha.gradeC',
				'only' => 'scripts',
				'raw' => '1',
				'version' => $version,
			]
		);

		// Grade C has no mw.loader, so RLCONF vars don't reach mw.config —
		// forward them via the bootstrap payload.
		$jsConfigVars = $outputPage->getJsConfigVars();
		$title = $outputPage->getTitle();
		$canonicalSpecialPageName = null;
		if ( $title !== null ) {
			if ( $title->isSpecial( 'CreateAccount' ) ) {
				$canonicalSpecialPageName = 'CreateAccount';
			} elseif ( $title->isSpecial( 'Userlogin' ) ) {
				$canonicalSpecialPageName = 'Userlogin';
			}
		}
		$data = [
			'config' => [
				'wgCanonicalSpecialPageName' => $canonicalSpecialPageName,
				'wgAction' => $outputPage->getActionName(),
				'wgConfirmEditHCaptchaSiteKey' => $siteKey,
				'wgHCaptchaTriggerFormSubmission' =>
					(bool)( $jsConfigVars['wgHCaptchaTriggerFormSubmission'] ?? false ),
			],
			'messages' => $this->getGradeCMessages( $outputPage ),
			'configModule' => [
				'HCaptchaApiUrl' => $this->getHCaptchaApiUrl( $outputPage->getLanguage()->getCode() ),
				'HCaptchaApiUrlIntegrityHash' => $this->options->get( 'HCaptchaApiUrlIntegrityHash' ),
			],
		];

		// UTF8_OK (not ALL_OK) so < > & escape to \u003C etc.; ALL_OK leaves
		// them raw, which is unsafe for any string flowing into an inline <script>.
		$payload = FormatJson::encode( $data, false, FormatJson::UTF8_OK );
		$srcLiteral = Html::encodeJsVar( $bundleUrl );

		// startup.js drains NORLQ only when isCompatible() returns false.
		$html = '<script>'
			. 'window.__confirmEditHCaptchaGradeC=' . $payload . ';'
			. 'window.NORLQ=window.NORLQ||[];'
			. 'window.NORLQ.push(function(){'
			. 'var s=document.createElement("script");'
			. 's.src=' . $srcLiteral . ';'
			. 'document.head.appendChild(s);'
			. '});'
			. '</script>';

		$outputPage->addHeadItem( 'confirmedit-hcaptcha-gradec', $html );
	}

	/**
	 * @return array<string,string>
	 */
	private function getGradeCMessages( OutputPage $outputPage ): array {
		$messages = [];
		foreach ( self::RUNTIME_MESSAGE_KEYS as $key ) {
			$messages[$key] = $outputPage->msg( $key )->plain();
		}
		return $messages;
	}
}
