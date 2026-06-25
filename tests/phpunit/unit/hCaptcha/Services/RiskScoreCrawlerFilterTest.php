<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Tests\Unit\hCaptcha\Services;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\RiskScoreCrawlerFilter;
use MediaWiki\Request\WebRequest;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\RiskScoreCrawlerFilter
 */
class RiskScoreCrawlerFilterTest extends MediaWikiUnitTestCase {

	/** @dataProvider provideIsExcludedCrawler */
	public function testIsExcludedCrawler(
		bool $expected,
		array $patterns,
		string|false $userAgent
	): void {
		$filter = new RiskScoreCrawlerFilter(
			new ServiceOptions(
				RiskScoreCrawlerFilter::CONSTRUCTOR_OPTIONS,
				[ 'HCaptchaBlockedIpEditingScoreSkipUserAgents' => $patterns ]
			)
		);

		$request = $this->createMock( WebRequest::class );
		$request->method( 'getHeader' )
			->with( 'User-Agent' )
			->willReturn( $userAgent );

		$this->assertSame( $expected, $filter->isExcludedCrawler( $request ) );
	}

	public static function provideIsExcludedCrawler(): iterable {
		yield 'no patterns configured' => [ false, [], 'ExampleBot/1.1' ];
		yield 'no User-Agent header' => [ false, [ '#ExampleBot#i' ], false ];
		yield 'User-Agent matches the only pattern' => [
			true,
			[ '#ExampleBot#i' ],
			'ExampleBot/1.1 (+https://example.com/bot)',
		];
		yield 'User-Agent matches a later pattern' => [
			true,
			[ '#OtherCrawler#i', '#ExampleBot#i' ],
			'ExampleBot/1.1',
		];
		yield 'User-Agent matches no pattern' => [
			false,
			[ '#ExampleBot#i', '#OtherCrawler#i' ],
			'ExampleBrowser/5.0 (compatible)',
		];
	}
}
