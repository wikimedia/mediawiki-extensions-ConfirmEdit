<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\ConfirmEdit\Hooks\Handlers;

use MediaWiki\Extension\ConfirmEdit\hCaptcha\HCaptcha;
use MediaWiki\Output\Hook\BeforePageDisplayHook;

/**
 * Adds hCaptcha modules and config variables to edit pages where the user is
 * IP/range blocked, so that a risk score can be collected.
 */
class BeforePageDisplayHookHandler extends AbstractCaptchaHandler implements BeforePageDisplayHook {

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

		$captchaInstance = $this->captchaFactory->getGlobalInstanceFromContext( $out );
		if ( !$captchaInstance instanceof HCaptcha ) {
			return;
		}

		if ( $captchaInstance->canSkipCaptcha( $out->getUser() ) ) {
			return;
		}

		$blocks = $this->getBlocksRequiringHCaptcha(
			$out->getTitle(),
			$out->getUser()->getBlock()
		);

		if ( count( $blocks['local'] ) || count( $blocks['global'] ) ) {
			$out->addModules( 'ext.confirmEdit.hCaptcha' );
			$out->addJsConfigVars( [
				'wgHCaptchaBlockedIpEditingScoreCollectionConfig' => [
					'siteKey' => $siteKey,
					'localBlockIds' => $this->listBlockIds( $blocks['local'] ),
					'globalBlockIds' => $this->listBlockIds( $blocks['global'] ),
				],
			] );
		}
	}
}
