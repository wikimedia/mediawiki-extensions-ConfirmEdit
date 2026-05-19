<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\hCaptcha\Services;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\ConfirmEdit\CaptchaTriggers;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\HCaptcha;
use MediaWiki\Extension\ConfirmEdit\Hooks;
use MediaWiki\Html\Html;
use MediaWiki\Json\FormatJson;
use MediaWiki\MainConfigNames;
use MediaWiki\Output\OutputPage;
use MediaWiki\ResourceLoader\Context;
use MediaWiki\ResourceLoader\ResourceLoader;
use Wikimedia\Assert\Assert;

/**
 * Service used to de-duplicate the code for adding hCaptcha to the output in both
 * {@link HCaptcha} and {@link HTMLHCaptchaField}.
 */
class HCaptchaOutput {

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

	public function __construct(
		private readonly ServiceOptions $options,
		private readonly ResourceLoader $resourceLoader,
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
		if ( $outputPage->getTitle()->isSpecial( 'CreateAccount' ) ) {
			$action = CaptchaTriggers::CREATE_ACCOUNT;
		} elseif ( $outputPage->getTitle()->exists() ) {
			$action = CaptchaTriggers::EDIT;
		} else {
			$action = CaptchaTriggers::CREATE;
		}
		/** @var HCaptcha $simpleCaptcha */
		$simpleCaptcha = Hooks::getInstance( $action );
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
		if ( $useInvisibleMode ) {
			$hCaptchaElementAttribs['data-size'] = 'invisible';
		}
		$output = Html::element( 'div', $hCaptchaElementAttribs );

		$useSecureEnclave = $this->options->get( 'HCaptchaEnterprise' ) &&
			$this->options->get( 'HCaptchaSecureEnclave' );
		if ( !$useSecureEnclave ) {
			$hCaptchaApiUrl = $this->options->get( 'HCaptchaApiUrl' );
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
	 * The bundle's secureEnclave.js branches on wgCanonicalSpecialPageName ===
	 * 'CreateAccount' or wgAction === 'edit'|'submit'; only emit the bootstrap
	 * on those entry points.
	 */
	private function shouldEmitGradeCBootstrap( OutputPage $outputPage ): bool {
		$title = $outputPage->getTitle();
		if ( $title !== null && $title->isSpecial( 'CreateAccount' ) ) {
			return true;
		}
		$action = $outputPage->getRequest()->getVal( 'action' );
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
		$data = [
			'config' => [
				'wgCanonicalSpecialPageName' => $outputPage->getTitle()?->isSpecial( 'CreateAccount' )
					? 'CreateAccount'
					: null,
				'wgAction' => $outputPage->getRequest()->getVal( 'action' ) ?? 'view',
				'wgConfirmEditHCaptchaSiteKey' => $siteKey,
				'wgHCaptchaTriggerFormSubmission' =>
					(bool)( $jsConfigVars['wgHCaptchaTriggerFormSubmission'] ?? false ),
			],
			'messages' => $this->getGradeCMessages( $outputPage ),
			'configModule' => [
				'HCaptchaApiUrl' => $this->options->get( 'HCaptchaApiUrl' ),
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
