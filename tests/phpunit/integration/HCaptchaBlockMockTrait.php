<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration;

use MediaWiki\Block\AbstractBlock;
use MediaWiki\Block\BlockTarget;
use MediaWiki\Block\CompositeBlock;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Builds block mocks (single, IP/range and composite) for tests exercising
 * HCaptchaBlocksLookup's applicability checks.
 */
trait HCaptchaBlockMockTrait {

	private function createBlockMockWithTarget(
		bool $applies,
		?BlockTarget $target,
		string $originalClassName = AbstractBlock::class,
		int $id = 123
	): AbstractBlock&MockObject {
		$block = $this->createMock( $originalClassName );
		$block
			->method( 'getId' )
			->willReturn( $id );
		$block
			->method( 'appliesToTitle' )
			->willReturn( $applies );
		$block
			->method( 'appliesToRight' )
			->willReturn( $applies );
		$block
			->method( 'getTarget' )
			->willReturn( $target );

		return $block;
	}

	private function createCompositeBlockMockWithTarget(
		bool $applies,
		?BlockTarget $target,
		array $children
	): CompositeBlock&MockObject {
		/** @var CompositeBlock&MockObject $composite */
		$composite = $this->createBlockMockWithTarget(
			$applies,
			$target,
			CompositeBlock::class,
			456
		);

		$composite
			->method( 'getOriginalBlocks' )
			->willReturn( $children );

		return $composite;
	}
}
