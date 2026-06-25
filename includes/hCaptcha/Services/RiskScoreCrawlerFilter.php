<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\hCaptcha\Services;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Request\WebRequest;

/**
 * Excludes requests from hCaptcha blocked-IP risk-score collection when the
 * User-Agent matches a configured crawler pattern
 * ($wgHCaptchaBlockedIpEditingScoreSkipUserAgents). Shared by every surface
 * that collects a score.
 */
class RiskScoreCrawlerFilter {

	public const CONSTRUCTOR_OPTIONS = [
		'HCaptchaBlockedIpEditingScoreSkipUserAgents',
	];

	/** @var string[] */
	private array $skipUserAgentPatterns;

	public function __construct( ServiceOptions $options ) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->skipUserAgentPatterns = $options->get( 'HCaptchaBlockedIpEditingScoreSkipUserAgents' );
	}

	/**
	 * Whether the request's User-Agent matches a configured crawler pattern.
	 */
	public function isExcludedCrawler( WebRequest $request ): bool {
		if ( !$this->skipUserAgentPatterns ) {
			return false;
		}

		$userAgent = $request->getHeader( 'User-Agent' );
		if ( $userAgent === false ) {
			return false;
		}

		return array_any(
			$this->skipUserAgentPatterns,
			static fn ( string $pattern ) => preg_match( $pattern, $userAgent )
		);
	}
}
