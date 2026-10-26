<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\ConfirmEdit\Hooks\Handlers;

use MediaWiki\Config\Config;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\HCaptcha;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\HCaptchaBlocksLookup;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\RiskScoreCrawlerFilter;
use MediaWiki\Extension\ConfirmEdit\Services\CaptchaFactory;
use MediaWiki\Output\Hook\BeforePageDisplayHook;

/**
 * Adds hCaptcha modules and config variables to edit pages where the user is
 * IP/range blocked, so that a risk score can be collected.
 */
class BeforePageDisplayHookHandler implements BeforePageDisplayHook {

	public function __construct(
		private readonly Config $config,
		private readonly CaptchaFactory $captchaFactory,
		private readonly HCaptchaBlocksLookup $blocksLookup,
		private readonly RiskScoreCrawlerFilter $crawlerFilter,
	) {
	}

	/** @inheritDoc */
	public function onBeforePageDisplay( $out, $skin ): void {
		$action = $out->getRequest()->getVal( 'action' ) ??
			$out->getRequest()->getVal( 'veaction' );
		if ( $action !== 'edit' && $action !== 'submit' ) {
			return;
		}

		$siteKey = $this->config->get( 'HCaptchaBlockedIpEditingScoreCollectionSiteKey' );
		if ( !$siteKey ) {
			return;
		}

		if ( $this->crawlerFilter->isExcludedCrawler( $out->getRequest() ) ) {
			return;
		}

		$captchaInstance = $this->captchaFactory->getGlobalInstanceFromContext( $out );
		if ( !$captchaInstance instanceof HCaptcha ) {
			return;
		}

		if ( $captchaInstance->canSkipCaptcha( $out->getUser() ) ) {
			return;
		}

		// Only IP/range blocks trigger risk-score collection; other block types must be ignored.
		$hasBlocks = $this->blocksLookup->hasBlocksRequiringHCaptcha(
			$out->getTitle(),
			$out->getUser()->getBlock()
		);

		if ( $hasBlocks ) {
			$out->addModules( 'ext.confirmEdit.hCaptcha' );
			$out->addJsConfigVars( [
				'wgHCaptchaBlockedIpEditingScoreCollectionSiteKey' => $siteKey,
			] );
		}
	}
}
