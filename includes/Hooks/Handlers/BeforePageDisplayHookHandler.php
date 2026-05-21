<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\ConfirmEdit\Hooks\Handlers;

use MediaWiki\Block\AbstractBlock;
use MediaWiki\Block\Block;
use MediaWiki\Block\BlockTargetWithIp;
use MediaWiki\Block\CompositeBlock;
use MediaWiki\Block\SystemBlock;
use MediaWiki\Config\Config;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\HCaptcha;
use MediaWiki\Extension\ConfirmEdit\Hooks;
use MediaWiki\Extension\GlobalBlocking\GlobalBlock;
use MediaWiki\Output\Hook\BeforePageDisplayHook;
use MediaWiki\Title\Title;

/**
 * Adds hCaptcha modules and config variables to edit pages where the user is
 * IP/range blocked, so that a risk score can be collected.
 */
class BeforePageDisplayHookHandler implements BeforePageDisplayHook {

	public function __construct(
		private readonly Config $config
	) {
	}

	/** @inheritDoc */
	public function onBeforePageDisplay( $out, $skin ): void {
		$action = $out->getRequest()->getVal( 'action' );
		if ( $action !== 'edit' && $action !== 'submit' ) {
			return;
		}

		$siteKey = $this->config->get( 'HCaptchaBlockedIpEditingScoreCollectionSiteKey' );
		if ( !$siteKey ) {
			return;
		}

		$captchaInstance = Hooks::getInstance(
			Hooks::getCaptchaTriggerActionFromTitle( $out->getTitle() )
		);
		if ( !$captchaInstance instanceof HCaptcha ) {
			return;
		}

		if ( $captchaInstance->canSkipCaptcha( $out->getUser() ) ) {
			return;
		}

		$blocks = $this->getBlockRequiringHCaptcha(
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

	/**
	 * Returns the blocks that require HCaptcha to be loaded on the current
	 * page, split into local and global blocks. The returned blocks are IP or
	 * IP range blocks that apply to the current page.
	 *
	 * For composite blocks, the specific child blocks that target an IP and
	 * apply to the current page are returned in addition to the composite block
	 * itself.
	 *
	 * @param Title $title Current page
	 * @param ?Block $block Block currently applying to this page, if any
	 * @return array{local: Block[], global: Block[]}
	 */
	private function getBlockRequiringHCaptcha(
		Title $title,
		?Block $block
	): array {
		if ( !$block || !$this->appliesToCurrentPage( $block, $title ) ) {
			return [
				'local' => [],
				'global' => []
			];
		}

		$local = [];
		$global = [];

		// Note a SystemBlock will always target an IP address,
		// so they will be handled by this conditional.
		if ( $block->getTarget() instanceof BlockTargetWithIp ) {
			if ( $block instanceof GlobalBlock ) {
				$global[] = $block;
			} else {
				$local[] = $block;
			}
		}

		if ( $block instanceof CompositeBlock ) {
			foreach ( $block->getOriginalBlocks() as $childBlock ) {
				if ( $childBlock->getTarget() instanceof BlockTargetWithIp &&
					$this->appliesToCurrentPage( $childBlock, $title ) ) {
					if ( $childBlock instanceof GlobalBlock ) {
						$global[] = $childBlock;
					} else {
						$local[] = $childBlock;
					}
				}
			}
		}

		return [
			'local' => $local,
			'global' => $global
		];
	}

	/**
	 * Checks whether the given block applies to the current page.
	 *
	 * For AbstractBlocks that are not SystemBlocks or GlobalBlocks, this
	 * delegates to AbstractBlock::appliesToTitle(). SystemBlocks and
	 * GlobalBlocks are always site-wide, so they always apply regardless of
	 * the current page.
	 *
	 * @param Block $block Block to check
	 * @param Title $title Current page
	 * @return bool
	 */
	private function appliesToCurrentPage( Block $block, Title $title ): bool {
		// System and Global blocks are always site-wide, so they always apply.
		if ( ( $block instanceof SystemBlock ) ||
			( $block instanceof GlobalBlock ) ) {
			return true;
		}

		if ( $block instanceof AbstractBlock ) {
			return $block->appliesToTitle( $title );
		}

		return true;
	}

	/**
	 * Returns a list with the block IDs from a list of block instances.
	 *
	 * @param Block[] $blocks
	 * @return int[]
	 */
	private function listBlockIds( array $blocks ): array {
		// Note the ID will be null for system blocks, therefore
		// we need to filter them and then reindex the array.
		return array_values(
			array_filter(
				array_map(
					static fn ( Block $block ) => $block->getId(),
					$blocks
				)
			)
		);
	}
}
