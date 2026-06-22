<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\ConfirmEdit\Hooks\Handlers;

use MediaWiki\Block\Block;
use MediaWiki\Block\BlockTargetWithIp;
use MediaWiki\Block\CompositeBlock;
use MediaWiki\Config\Config;
use MediaWiki\Deferred\DeferredUpdates;
use MediaWiki\Extension\ConfirmEdit\CaptchaTriggers;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\HCaptcha;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\HCaptchaBlocksLookup;
use MediaWiki\Extension\ConfirmEdit\Hooks\HookRunner;
use MediaWiki\Extension\ConfirmEdit\Services\CaptchaFactory;
use MediaWiki\Extension\GlobalBlocking\GlobalBlock;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Output\Hook\BeforePageDisplayHook;
use MediaWiki\Output\OutputPage;
use MediaWiki\Request\WebRequest;

/**
 * Collects an hCaptcha risk score when an IP/range block prevents the action.
 *
 * On edit pages, adds the hCaptcha modules and config variables so the widget
 * can run client-side. On a blocked Special:CreateAccount submission, reuses
 * the token already submitted with the form to obtain a score server-side.
 */
class BeforePageDisplayHookHandler implements BeforePageDisplayHook {

	public function __construct(
		private readonly Config $config,
		private readonly CaptchaFactory $captchaFactory,
		private readonly HCaptchaBlocksLookup $blocksLookup,
		private readonly HookContainer $hookContainer,
	) {
	}

	/** @inheritDoc */
	public function onBeforePageDisplay( $out, $skin ): void {
		$title = $out->getTitle();
		if ( $title && $title->isSpecial( 'CreateAccount' ) ) {
			$this->maybeCollectAccountCreationRiskScore( $out );
			return;
		}

		$action = $out->getRequest()->getVal( 'action' ) ??
			$out->getRequest()->getVal( 'veaction' );
		if ( $action !== 'edit' && $action !== 'submit' ) {
			return;
		}

		$siteKey = $this->config->get( 'HCaptchaBlockedIpEditingScoreCollectionSiteKey' );
		if ( !$siteKey ) {
			return;
		}

		if ( $this->isExcludedCrawler( $out->getRequest() ) ) {
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

	/**
	 * Whether the request's User-Agent matches a configured crawler pattern.
	 *
	 * Patterns are PCRE strings (with delimiters) supplied via
	 * $wgHCaptchaBlockedIpEditingScoreSkipUserAgents. The list defaults to
	 * empty, in which case no request is excluded.
	 */
	private function isExcludedCrawler( WebRequest $request ): bool {
		$patterns = $this->config->get( 'HCaptchaBlockedIpEditingScoreSkipUserAgents' );
		if ( !$patterns ) {
			return false;
		}

		$userAgent = $request->getHeader( 'User-Agent' );
		if ( $userAgent === false ) {
			return false;
		}
		return array_any( $patterns, static fn ( $pattern ) => preg_match( $pattern, $userAgent ) );
	}

	/**
	 * On a blocked Special:CreateAccount submission, reuse the hCaptcha token
	 * from the form for a risk score. The block check short-circuits before the
	 * captcha provider runs (AuthManager::beginAccountCreation), so the token is
	 * still unused here.
	 */
	private function maybeCollectAccountCreationRiskScore( OutputPage $out ): void {
		$request = $out->getRequest();
		if ( !$request->wasPosted() || !$this->config->get( 'HCaptchaUseRiskScore' ) ) {
			return;
		}

		$captchaInstance = $this->captchaFactory->getGlobalInstance( CaptchaTriggers::CREATE_ACCOUNT );
		if ( !$captchaInstance instanceof HCaptcha ) {
			return;
		}

		// Nothing to score without a token (no-JS client, or a user who can skip
		// the captcha and so was never shown the widget).
		if ( !$request->getVal( 'h-captcha-response' ) ) {
			return;
		}

		$user = $out->getUser();
		$blocks = $this->getCreateAccountBlocksRequiringHCaptcha( $user->getBlock() );
		if ( !count( $blocks['local'] ) && !count( $blocks['global'] ) ) {
			return;
		}

		$localBlockIds = $this->blocksLookup->listBlockIds( $blocks['local'] );
		$globalBlockIds = $this->blocksLookup->listBlockIds( $blocks['global'] );
		$hookRunner = new HookRunner( $this->hookContainer );

		// Defer the siteverify HTTP call until after the response is sent.
		DeferredUpdates::addCallableUpdate(
			static function () use (
				$captchaInstance, $request, $user, $localBlockIds, $globalBlockIds, $hookRunner
			) {
				$captchaInstance->passCaptchaFromRequest( $request, $user );
				$score = $captchaInstance->retrieveSessionScore( 'hCaptcha-score', $user->getName() );
				$riskScore = is_numeric( $score ) ? (float)$score : -1.0;
				$hookRunner->onConfirmEditHCaptchaRiskScoreRetrievedForBlocks(
					$riskScore,
					$localBlockIds,
					$globalBlockIds,
					$user,
					'',
					$request
				);
			}
		);
	}

	/**
	 * Returns the IP/range blocks that prevent account creation, split into
	 * local and global. Mirrors getBlocksRequiringHCaptcha() but keys on the
	 * 'createaccount' right rather than page applicability.
	 *
	 * @param ?Block $block Block currently applying to the user, if any
	 * @return array{local: Block[], global: GlobalBlock[]}
	 */
	private function getCreateAccountBlocksRequiringHCaptcha( ?Block $block ): array {
		$local = [];
		$global = [];

		if ( !$block ) {
			return [ 'local' => $local, 'global' => $global ];
		}

		$candidates = $block instanceof CompositeBlock ? $block->getOriginalBlocks() : [ $block ];
		foreach ( $candidates as $candidate ) {
			if ( !$candidate->getTarget() instanceof BlockTargetWithIp ||
				!$candidate->appliesToRight( 'createaccount' )
			) {
				continue;
			}
			if ( $candidate instanceof GlobalBlock ) {
				$global[] = $candidate;
			} else {
				$local[] = $candidate;
			}
		}

		return [ 'local' => $local, 'global' => $global ];
	}
}
