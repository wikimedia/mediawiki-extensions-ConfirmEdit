<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration\hCaptcha\Services;

use InvalidArgumentException;
use MediaWiki\Block\AbstractBlock;
use MediaWiki\Block\AnonIpBlockTarget;
use MediaWiki\Block\Block;
use MediaWiki\Block\RangeBlockTarget;
use MediaWiki\Block\SystemBlock;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\HCaptchaBlocksLookup;
use MediaWiki\Extension\ConfirmEdit\Tests\Integration\HCaptchaBlockMockTrait;
use MediaWiki\Extension\GlobalBlocking\GlobalBlock;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\HCaptchaBlocksLookup
 */
class HCaptchaBlocksLookupTest extends MediaWikiIntegrationTestCase {
	use HCaptchaBlockMockTrait;

	private HCaptchaBlocksLookup $blocksLookup;
	private Title $title;

	protected function setUp(): void {
		parent::setUp();

		$this->blocksLookup = $this->getServiceContainer()->get( 'ConfirmEditHCaptchaBlocksLookup' );
		$this->title = Title::makeTitle( NS_MAIN, 'Test' );
	}

	/** @dataProvider provideGetBlocksRequiringHCaptcha */
	public function testGetBlocksRequiringHCaptcha(
		string $blockType,
		array $expectedBlockIds
	): void {
		$block = $blockType === 'none' ? null : $this->createBlockMock( $blockType );

		$result = $this->blocksLookup->getBlocksRequiringHCaptcha( $this->title, $block );

		$this->assertSame(
			$expectedBlockIds,
			array_map( static fn ( Block $b ) => $b->getId(), $result )
		);
	}

	public static function provideGetBlocksRequiringHCaptcha(): iterable {
		yield 'No block' => [
			'blockType' => 'none',
			'expectedBlockIds' => [],
		];
		yield 'Site-wide IP block' => [
			'blockType' => 'ip',
			'expectedBlockIds' => [ 123 ],
		];
		yield 'Site-wide range block' => [
			'blockType' => 'range',
			'expectedBlockIds' => [ 123 ],
		];
		yield 'User block with no IP target' => [
			'blockType' => 'user',
			'expectedBlockIds' => [],
		];
		yield 'IP block that does not apply to the title' => [
			'blockType' => 'ip_partial',
			'expectedBlockIds' => [],
		];
		yield 'Composite block with an IP block child' => [
			'blockType' => 'composite_ip',
			'expectedBlockIds' => [ 123 ],
		];
		yield 'Composite block with no IP block child' => [
			'blockType' => 'composite_user',
			'expectedBlockIds' => [],
		];
		yield 'SystemBlock with an IP target always applies' => [
			'blockType' => 'system_block',
			'expectedBlockIds' => [ 123 ],
		];
		yield 'Composite block with a SystemBlock IP child' => [
			'blockType' => 'composite_system_block',
			'expectedBlockIds' => [ 123 ],
		];
		yield 'Composite block with multiple IP block children' => [
			'blockType' => 'composite_multi_ip',
			'expectedBlockIds' => [ 123, 124 ],
		];
		yield 'Composite block whose own target is an IP only returns child IDs' => [
			'blockType' => 'composite_ip_with_ip_target',
			'expectedBlockIds' => [ 123 ],
		];
		yield 'Global IP block' => [
			'blockType' => 'global_block',
			'expectedBlockIds' => [ 123 ],
		];
		yield 'Partial global block that does not apply to the title' => [
			'blockType' => 'global_block_partial',
			'expectedBlockIds' => [],
		];
		yield 'Composite block with a GlobalBlock IP child' => [
			'blockType' => 'composite_global_block',
			'expectedBlockIds' => [ 123 ],
		];
		yield 'Composite block with both a local and a global IP child' => [
			'blockType' => 'composite_mixed',
			'expectedBlockIds' => [ 123, 124 ],
		];
	}

	/** @dataProvider provideHasBlocksRequiringHCaptcha */
	public function testHasBlocksRequiringHCaptcha( string $blockType, bool $expected ): void {
		$block = $blockType === 'none' ? null : $this->createBlockMock( $blockType );

		$this->assertSame(
			$expected,
			$this->blocksLookup->hasBlocksRequiringHCaptcha( $this->title, $block )
		);
	}

	public static function provideHasBlocksRequiringHCaptcha(): iterable {
		yield 'No block' => [ 'blockType' => 'none', 'expected' => false ];
		yield 'Site-wide IP block' => [ 'blockType' => 'ip', 'expected' => true ];
		yield 'User block with no IP target' => [ 'blockType' => 'user', 'expected' => false ];
		yield 'Global IP block' => [ 'blockType' => 'global_block', 'expected' => true ];
	}

	/**
	 * Creates a mock block for use in testGetBlocksRequiringHCaptcha.
	 *
	 * The block mocks' appliesToTitle flag controls applicability against the
	 * title passed to the service.
	 */
	private function createBlockMock( string $setup ): Block {
		$mock = match ( $setup ) {
			'ip' => $this->createBlockMockWithTarget(
				true,
				$this->createMock( AnonIpBlockTarget::class )
			),
			'range' => $this->createBlockMockWithTarget(
				true,
				$this->createMock( RangeBlockTarget::class )
			),
			'user' => $this->createBlockMockWithTarget(
				true,
				null
			),
			'ip_partial' => $this->createBlockMockWithTarget(
				false,
				$this->createMock( AnonIpBlockTarget::class )
			),
			'composite_ip' => $this->createCompositeBlockMockWithTarget(
				true,
				null,
				[
					$this->createBlockMockWithTarget(
						true,
						$this->createMock( AnonIpBlockTarget::class )
					)
				]
			),
			'composite_user' => $this->createCompositeBlockMockWithTarget(
				true,
				null,
				[ $this->createBlockMockWithTarget( true, null ) ]
			),
			'system_block' => $this->createBlockMockWithTarget(
				false,
				$this->createMock( AnonIpBlockTarget::class ),
				SystemBlock::class
			),
			// The child's appliesToTitle returns false to prove it is never
			// consulted for SystemBlock children.
			'composite_system_block' => $this->createCompositeBlockMockWithTarget(
				true,
				null,
				[
					$this->createBlockMockWithTarget(
						false,
						$this->createMock( AnonIpBlockTarget::class ),
						SystemBlock::class
					)
				]
			),
			'composite_ip_with_ip_target' => $this->createCompositeBlockMockWithTarget(
				true,
				$this->createMock( AnonIpBlockTarget::class ),
				[
					$this->createBlockMockWithTarget(
						true,
						$this->createMock( AnonIpBlockTarget::class )
					)
				]
			),
			'composite_multi_ip' => $this->createCompositeBlockMockWithTarget(
				true,
				null,
				[
					$this->createBlockMockWithTarget(
						true,
						$this->createMock( AnonIpBlockTarget::class )
					),
					$this->createBlockMockWithTarget(
						true,
						$this->createMock( RangeBlockTarget::class ),
						AbstractBlock::class,
						124
					),
				]
			),
			default => null,
		};

		if ( $mock ) {
			return $mock;
		}

		// Next block types require GlobalBlocking to be installed
		$this->markTestSkippedIfExtensionNotLoaded( 'GlobalBlocking' );

		return match ( $setup ) {
			'global_block' => $this->createBlockMockWithTarget(
				true,
				$this->createMock( AnonIpBlockTarget::class ),
				GlobalBlock::class
			),
			'global_block_partial' => $this->createBlockMockWithTarget(
				false,
				$this->createMock( AnonIpBlockTarget::class ),
				GlobalBlock::class
			),
			'composite_global_block' => $this->createCompositeBlockMockWithTarget(
				true,
				null,
				[
					$this->createBlockMockWithTarget(
						true,
						$this->createMock( AnonIpBlockTarget::class ),
						GlobalBlock::class
					)
				]
			),
			'composite_mixed' => $this->createCompositeBlockMockWithTarget(
				true,
				null,
				[
					$this->createBlockMockWithTarget(
						true,
						$this->createMock( AnonIpBlockTarget::class )
					),
					$this->createBlockMockWithTarget(
						true,
						$this->createMock( AnonIpBlockTarget::class ),
						GlobalBlock::class,
						124
					),
				]
			),
			default => throw new InvalidArgumentException( "Unknown setup: $setup" ),
		};
	}
}
