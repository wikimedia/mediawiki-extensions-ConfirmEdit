<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Hooks\Handlers;

use MediaWiki\Config\Config;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\HCaptcha;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\HCaptchaBlocksLookup;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\RiskScoreCrawlerFilter;
use MediaWiki\Extension\ConfirmEdit\Services\CaptchaFactory;
use MediaWiki\Extension\VisualEditor\Services\VisualEditorAvailabilityLookup;
use MediaWiki\Output\Hook\MakeGlobalVariablesScriptHook;
use MediaWiki\Output\OutputPage;
use MediaWiki\Registration\ExtensionRegistry;
use MobileContext;

/**
 * Adds a JavaScript configuration variable that indicates what kind of captcha is required to be completed for
 * editing the page that the user is viewing or editing. This is only the generic sense, as the specific content
 * of the edit may cause a captcha to appear at save time.
 *
 * Used by the VisualEditor and MobileFrontend integrations to determine if they need to display hCaptcha to the
 * user.
 */
class MakeGlobalVariablesScriptHookHandler implements MakeGlobalVariablesScriptHook {

	public function __construct(
		private readonly ExtensionRegistry $extensionRegistry,
		private readonly Config $config,
		private readonly CaptchaFactory $captchaFactory,
		private readonly HCaptchaBlocksLookup $blocksLookup,
		private readonly RiskScoreCrawlerFilter $crawlerFilter,
		private readonly ?VisualEditorAvailabilityLookup $visualEditorAvailabilityLookup = null,
		private readonly ?MobileContext $mobileContext = null
	) {
	}

	/** @inheritDoc */
	public function onMakeGlobalVariablesScript( &$vars, $out ): void {
		if ( !$out->canUseWikiPage() ) {
			$vars['wgConfirmEditCaptchaNeededForGenericEdit'] = false;
			return;
		}

		$mobileFrontendAvailable = $this->extensionRegistry->isLoaded( 'MobileFrontend' );
		$visualEditorAvailable = $this->extensionRegistry->isLoaded( 'VisualEditor' );

		if ( $visualEditorAvailable && $this->visualEditorAvailabilityLookup !== null ) {
			$visualEditorAvailable = $this->visualEditorAvailabilityLookup->isAvailable(
				$out->getTitle(), $out->getRequest(), $out->getUser()
			);
		}

		if ( $mobileFrontendAvailable && $this->mobileContext !== null ) {
			$mobileFrontendAvailable = $this->mobileContext->shouldDisplayMobileView();
		}

		$captchaNeededForEdit = false;

		$captchaInstance = $this->captchaFactory->getGlobalInstanceFromContext( $out );
		if (
			$captchaInstance->shouldCheck( $out->getWikiPage(), '', '', $out->getContext() )
		) {
			$captchaNeededForEdit = strtolower( $captchaInstance->getName() );
		}

		$vars['wgConfirmEditCaptchaNeededForGenericEdit'] = $captchaNeededForEdit;
		$vars['wgConfirmEditForceShowCaptcha'] = $captchaInstance->shouldForceShowCaptcha();

		// instanceof check necessary for Phan to be happy
		if ( $captchaNeededForEdit === 'hcaptcha' && $captchaInstance instanceof HCaptcha ) {
			$vars['wgConfirmEditHCaptchaSiteKey'] = $captchaInstance->getSiteKeyForAction();
		}

		// We want to load the MobileFrontend hCaptcha module if the user may need
		// to complete hCaptcha for their edit. This is intentionally not based on
		// SimpleCaptcha::shouldCheck because if AbuseFilter is installed then
		// a CAPTCHA may be required based on the content of the edit. Users who
		// can skip captchas are excluded, since they will never be shown one.
		$usingHCaptcha = strtolower( $captchaInstance->getName() ) === 'hcaptcha' &&
			!$captchaInstance->canSkipCaptcha( $out->getUser() );

		if (
			$mobileFrontendAvailable &&
			$this->config->get( 'HCaptchaEnabledInMobileFrontend' ) &&
			$usingHCaptcha
		) {
			if ( $this->extensionRegistry->isLoaded( 'Abuse Filter' ) ) {
				$vars['wgConfirmEditMobileHCaptchaAbuseFilterEnabled'] = true;
			}

			$this->addHCaptchaModules( $out, $vars, true );
			$this->addIPBlocksScoreCollectionVars( $out );
		} elseif ( $visualEditorAvailable && $usingHCaptcha ) {
			$this->addHCaptchaModules( $out, $vars, $mobileFrontendAvailable );
			$this->addIPBlocksScoreCollectionVars( $out );
		}
	}

	/**
	 * Adds the HCaptcha modules to the current page.
	 *
	 * If the $initMobileFrontendModules flag is set, this additionally adds a
	 * variable to the page that makes the MobileFrontend call additional
	 * initialization code.
	 *
	 * @param OutputPage $out Current page
	 * @param array &$vars Page variables
	 * @param bool $initMobileFrontendModules Whether to call MF-specific init code
	 * @return void
	 */
	private function addHCaptchaModules(
		OutputPage $out,
		array &$vars,
		bool $initMobileFrontendModules
	): void {
		$requiredModule = 'ext.confirmEdit.hCaptcha';

		if ( $initMobileFrontendModules ) {
			$mfInitModulesKey = 'wgMobileFrontendSourceEditorInitializeModules';
			$modulesToInit = $vars[$mfInitModulesKey] ?? [];

			if ( !in_array( $requiredModule, $modulesToInit ) ) {
				$modulesToInit[] = $requiredModule;
			}

			$vars[$mfInitModulesKey] = $modulesToInit;
		}

		$out->addModules( $requiredModule );
	}

	private function addIPBlocksScoreCollectionVars( OutputPage $out ): void {
		if ( $this->crawlerFilter->isExcludedCrawler( $out->getRequest() ) ) {
			return;
		}

		$siteKey = $this->config->get( 'HCaptchaBlockedIpEditingScoreCollectionSiteKey' );
		if ( $siteKey ) {
			// For MobileFrontend and VisualEditor, we need to always provide
			// the key used for blocked edit notices, since they may be shown
			// without a new page load.
			// Only IP/range blocks trigger risk-score collection; other block types must be ignored.
			$hasBlocks = $this->blocksLookup->hasBlocksRequiringHCaptcha(
				$out->getTitle(),
				$out->getUser()->getBlock()
			);
			if ( $hasBlocks ) {
				$out->addJsConfigVars( [
					'wgHCaptchaBlockedIpEditingScoreCollectionSiteKey' => $siteKey
				] );
			}
		}
	}
}
