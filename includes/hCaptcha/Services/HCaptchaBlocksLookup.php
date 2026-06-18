<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\hCaptcha\Services;

use MediaWiki\Block\AbstractBlock;
use MediaWiki\Block\Block;
use MediaWiki\Block\BlockTargetWithIp;
use MediaWiki\Block\CompositeBlock;
use MediaWiki\Block\SystemBlock;
use MediaWiki\Title\Title;

/**
 * Resolves which blocks require hCaptcha to be loaded on a given page, and the
 * IDs of those blocks. This logic runs server-side both when emitting the
 * edit-page widget config and when recording a risk score, so the block IDs are
 * never trusted from the client.
 */
class HCaptchaBlocksLookup {

	/**
	 * Returns the blocks that require HCaptcha to be loaded on the current
	 * page, split into local and global blocks. The returned blocks are IP or
	 * IP range blocks that apply to the current page.
	 *
	 * For composite blocks, only the specific child blocks that target an IP
	 * and apply to the current page are returned, not the composite block itself.
	 *
	 * This is used by hook handlers associated with collecting risk scores for
	 * blocked edit notices that need to extract which blocks trigger such
	 * behavior when collecting risk scores is enabled.
	 *
	 * @param ?Title $title Current page, or null when the output has no title
	 * @param ?Block $block Block currently applying to this page, if any
	 * @return Block[]
	 */
	public function getBlocksRequiringHCaptcha(
		?Title $title,
		?Block $block
	): array {
		if ( !$title || !$block || !$this->appliesToCurrentPage( $block, $title ) ) {
			return [];
		}

		$blocks = [];

		if ( !( $block instanceof CompositeBlock ) ) {
			if ( $block->getTarget() instanceof BlockTargetWithIp ) {
				$blocks[] = $block;
			}
		} else {
			foreach ( $block->getOriginalBlocks() as $childBlock ) {
				if ( $childBlock->getTarget() instanceof BlockTargetWithIp &&
					$this->appliesToCurrentPage( $childBlock, $title ) ) {
					$blocks[] = $childBlock;
				}
			}
		}

		return $blocks;
	}

	/**
	 * Checks whether any block requires HCaptcha to be loaded on the current
	 * page. Use this instead of getBlocksRequiringHCaptcha() when only the
	 * existence of such blocks matters, not their IDs.
	 *
	 * @param ?Title $title Current page, or null when the output has no title
	 * @param ?Block $block Block currently applying to this page, if any
	 */
	public function hasBlocksRequiringHCaptcha( ?Title $title, ?Block $block ): bool {
		if ( !$title || !$block || !$this->appliesToCurrentPage( $block, $title ) ) {
			return false;
		}

		if ( !( $block instanceof CompositeBlock ) ) {
			return $block->getTarget() instanceof BlockTargetWithIp;
		}

		foreach ( $block->getOriginalBlocks() as $childBlock ) {
			if ( $childBlock->getTarget() instanceof BlockTargetWithIp &&
				$this->appliesToCurrentPage( $childBlock, $title ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks whether the given block applies to the current page.
	 *
	 * For AbstractBlocks that are not SystemBlocks, this delegates to
	 * AbstractBlock::appliesToTitle(). SystemBlocks are always site-wide,
	 * so they always apply regardless of the current page.
	 */
	private function appliesToCurrentPage( Block $block, Title $title ): bool {
		// System blocks are always site-wide, so they always apply.
		if ( $block instanceof SystemBlock ) {
			return true;
		}

		if ( $block instanceof AbstractBlock ) {
			return $block->appliesToTitle( $title );
		}

		return true;
	}
}
