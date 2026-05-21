<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\ConfirmEdit\Hooks\Handlers;

use MediaWiki\Block\AbstractBlock;
use MediaWiki\Block\Block;
use MediaWiki\Block\BlockTargetWithIp;
use MediaWiki\Block\CompositeBlock;
use MediaWiki\Block\SystemBlock;
use MediaWiki\Config\Config;
use MediaWiki\Extension\ConfirmEdit\Services\CaptchaFactory;
use MediaWiki\Extension\GlobalBlocking\GlobalBlock;
use MediaWiki\Title\Title;

abstract class AbstractCaptchaHandler {
	public function __construct(
		protected readonly Config $config,
		protected readonly CaptchaFactory $captchaFactory,
	) {
	}

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
	 * @param Title $title Current page
	 * @param ?Block $block Block currently applying to this page, if any
	 * @return array{local: Block[], global: GlobalBlock[]}
	 */
	protected function getBlocksRequiringHCaptcha(
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

		if ( !( $block instanceof CompositeBlock ) ) {
			if ( $block->getTarget() instanceof BlockTargetWithIp ) {
				if ( $block instanceof GlobalBlock ) {
					$global[] = $block;
				} else {
					$local[] = $block;
				}
			}
		} else {
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
	 * Returns a list with the block IDs from a list of block instances.
	 *
	 * @param Block[] $blocks
	 * @return int[]
	 */
	protected function listBlockIds( array $blocks ): array {
		return array_values(
			array_filter(
				array_map(
					static fn ( Block $block ) => $block->getId(),
					$blocks
				)
			)
		);
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
