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
		array $expectedLocalBlockIds,
		array $expectedGlobalBlockIds
	): void {
		$block = $blockType === 'none' ? null : $this->createBlockMock( $blockType );

		$result = $this->blocksLookup->getBlocksRequiringHCaptcha( $this->title, $block );

		$this->assertSame(
			$expectedLocalBlockIds,
			array_map( static fn ( Block $b ) => $b->getId(), $result['local'] )
		);
		$this->assertSame(
			$expectedGlobalBlockIds,
			array_map( static fn ( Block $b ) => $b->getId(), $result['global'] )
		);
	}

	public static function provideGetBlocksRequiringHCaptcha(): iterable {
		yield 'No block' => [
			'blockType' => 'none',
			'expectedLocalBlockIds' => [],
			'expectedGlobalBlockIds' => [],
		];
		yield 'Site-wide IP block' => [
			'blockType' => 'ip',
			'expectedLocalBlockIds' => [ 123 ],
			'expectedGlobalBlockIds' => [],
		];
		yield 'Site-wide range block' => [
			'blockType' => 'range',
			'expectedLocalBlockIds' => [ 123 ],
			'expectedGlobalBlockIds' => [],
		];
		yield 'User block with no IP target' => [
			'blockType' => 'user',
			'expectedLocalBlockIds' => [],
			'expectedGlobalBlockIds' => [],
		];
		yield 'IP block that does not apply to the title' => [
			'blockType' => 'ip_partial',
			'expectedLocalBlockIds' => [],
			'expectedGlobalBlockIds' => [],
		];
		yield 'Composite block with an IP block child' => [
			'blockType' => 'composite_ip',
			'expectedLocalBlockIds' => [ 123 ],
			'expectedGlobalBlockIds' => [],
		];
		yield 'Composite block with no IP block child' => [
			'blockType' => 'composite_user',
			'expectedLocalBlockIds' => [],
			'expectedGlobalBlockIds' => [],
		];
		yield 'SystemBlock with an IP target always applies' => [
			'blockType' => 'system_block',
			'expectedLocalBlockIds' => [ 123 ],
			'expectedGlobalBlockIds' => [],
		];
		yield 'Composite block with a SystemBlock IP child' => [
			'blockType' => 'composite_system_block',
			'expectedLocalBlockIds' => [ 123 ],
			'expectedGlobalBlockIds' => [],
		];
		yield 'Composite block with multiple IP block children' => [
			'blockType' => 'composite_multi_ip',
			'expectedLocalBlockIds' => [ 123, 124 ],
			'expectedGlobalBlockIds' => [],
		];
		yield 'Composite block whose own target is an IP only returns child IDs' => [
			'blockType' => 'composite_ip_with_ip_target',
			'expectedLocalBlockIds' => [ 123 ],
			'expectedGlobalBlockIds' => [],
		];
		yield 'Global IP block' => [
			'blockType' => 'global_block',
			'expectedLocalBlockIds' => [],
			'expectedGlobalBlockIds' => [ 123 ],
		];
		yield 'Partial global block that does not apply to the title' => [
			'blockType' => 'global_block_partial',
			'expectedLocalBlockIds' => [],
			'expectedGlobalBlockIds' => [],
		];
		yield 'Composite block with a GlobalBlock IP child' => [
			'blockType' => 'composite_global_block',
			'expectedLocalBlockIds' => [],
			'expectedGlobalBlockIds' => [ 123 ],
		];
		yield 'Composite block with both a local and a global IP child' => [
			'blockType' => 'composite_mixed',
			'expectedLocalBlockIds' => [ 123 ],
			'expectedGlobalBlockIds' => [ 124 ],
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

	/** @dataProvider provideListBlockIds */
	public function testListBlockIds( array $ids, array $expected ): void {
		$blocks = array_map(
			function ( int $id ): Block {
				$block = $this->createMock( AbstractBlock::class );
				$block
					->method( 'getId' )
					->willReturn( $id );

				return $block;
			},
			$ids
		);

		$this->assertSame( $expected, $this->blocksLookup->listBlockIds( $blocks ) );
	}

	public static function provideListBlockIds(): iterable {
		yield 'Empty list' => [ 'ids' => [], 'expected' => [] ];
		yield 'Returns array_values of the IDs' => [ 'ids' => [ 5, 7, 9 ], 'expected' => [ 5, 7, 9 ] ];
		yield 'Filters out a zero ID' => [ 'ids' => [ 0, 7 ], 'expected' => [ 7 ] ];
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
