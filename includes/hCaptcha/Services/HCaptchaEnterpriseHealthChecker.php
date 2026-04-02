<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\hCaptcha\Services;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\HCaptcha;
use Psr\Log\LoggerInterface;
use Wikimedia\ObjectCache\BagOStuff;
use Wikimedia\ObjectCache\WANObjectCache;
use Wikimedia\Stats\StatsFactory;

/**
 * Monitors hCaptcha availability and triggers failover to FancyCaptcha when
 * the service is unhealthy.
 *
 * How it works:
 *
 * When a user submits a captcha, HCaptcha::passCaptcha() sends the token to
 * hCaptcha's SiteVerify API for validation. If that HTTP request fails (network
 * error, timeout, or malformed response), it calls incrementSiteVerifyApiErrorCount(),
 * which bumps a counter in memcached with a 1-minute TTL.
 *
 * On every page render that might show a captcha, isAvailable() is called. It
 * checks whether the SiteVerify error count has reached the configured threshold
 * ($wgHCaptchaEnterpriseHealthCheckSiteVerifyErrorThreshold, default 5). If so,
 * hCaptcha is marked unavailable for $wgHCaptchaEnterpriseHealthCheckFailoverDuration
 * seconds (default 600), during which all captcha challenges use FancyCaptcha instead.
 *
 * The availability result is cached in WANObjectCache for 1 minute, so the
 * memcached error counter is only checked roughly once per minute per data center.
 * Within a single request, an in-process cache avoids repeated lookups.
 *
 * Observability:
 * - Logs: channel "captcha", warning when entering failover
 * - Prometheus: ConfirmEdit_hcaptcha_enterprise_failover_total{reason="siteverify_errors"}
 * - Prometheus: ConfirmEdit_hcaptcha_enterprise_health_checker_is_available_seconds{result}
 *
 * This health checker is reactive: it only detects problems after real users
 * experience SiteVerify failures. It does not proactively probe hCaptcha endpoints.
 */
class HCaptchaEnterpriseHealthChecker {

	public const CONSTRUCTOR_OPTIONS = [
		'HCaptchaEnterpriseHealthCheckSiteVerifyErrorThreshold',
		'HCaptchaEnterpriseHealthCheckFailoverDuration',
	];
	private const CACHE_SITEVERIFY_ERROR_COUNT_KEY = 'confirmedit-hcaptcha-siteverify-error-count';
	private const CACHE_AVAILABLE_KEY = 'confirmedit-hcaptcha-available';

	private ?bool $isAvailable = null;

	public function __construct(
		private readonly ServiceOptions $options,
		private readonly LoggerInterface $logger,
		private readonly BagOStuff $bagOStuffCache,
		private readonly WANObjectCache $wanObjectCache,
		private readonly StatsFactory $statsFactory,
	) {
		$this->options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
	}

	/**
	 * Intended to be called from HCaptcha::passCaptcha(), when the request to /siteverify
	 * fails due to HTTP or JSON decoding errors. We do not want to accumulate other errors
	 * (e.g. incorrect token, or expired token, etc.) here.
	 *
	 * @see HCaptcha::passCaptcha()
	 */
	public function incrementSiteVerifyApiErrorCount(): void {
		$this->bagOStuffCache->incrWithInit(
			$this->bagOStuffCache->makeGlobalKey( self::CACHE_SITEVERIFY_ERROR_COUNT_KEY ),
			$this->bagOStuffCache::TTL_MINUTE
		);
	}

	/**
	 * Intended for use in a hook handler for onConfirmEditCaptchaClass, to decide if a fallback
	 * captcha type is needed in place of hCaptcha Enterprise.
	 *
	 * Checks whether user-generated requests to $wgHCaptchaVerifyUrl are failing above the
	 * configured threshold. If so, we enter a failover mode.
	 *
	 * @return bool true if the hCaptcha service is considered to be available, false otherwise.
	 */
	public function isAvailable(): bool {
		$timer = $this->statsFactory->withComponent( 'ConfirmEdit' )
			->getTiming( 'hcaptcha_enterprise_health_checker_is_available_seconds' )
			->setLabel( 'result', 'unknown' )
			->start();

		// In-process cache, since this method can be invoked multiple times per request.
		if ( $this->isAvailable !== null ) {
			$timer->setLabel( 'result', $this->isAvailable ? 'true' : 'false' )->stop();
			return $this->isAvailable;
		}

		$this->isAvailable = (bool)$this->wanObjectCache->getWithSetCallback(
			$this->wanObjectCache->makeGlobalKey( self::CACHE_AVAILABLE_KEY ),
			$this->wanObjectCache::TTL_MINUTE,
			function ( $oldValue, &$ttl ) {
				$failoverDuration = $this->options->get( 'HCaptchaEnterpriseHealthCheckFailoverDuration' );
				// The SiteVerify request error count is incremented in
				// HCaptcha::passCaptcha(), when the SiteVerify request fails
				// with an HTTP or json-decode error.
				$failedSiteVerifyRequestCount = (int)$this->bagOStuffCache->get(
					$this->bagOStuffCache->makeGlobalKey( self::CACHE_SITEVERIFY_ERROR_COUNT_KEY )
				);
				$siteVerifyErrorThreshold = $this->options->get(
					'HCaptchaEnterpriseHealthCheckSiteVerifyErrorThreshold'
				);
				if ( $failedSiteVerifyRequestCount >= $siteVerifyErrorThreshold ) {
					$this->logger->warning(
						'hCaptcha unavailable due to SiteVerify errors: {count} >= {threshold}',
						[ 'count' => $failedSiteVerifyRequestCount, 'threshold' => $siteVerifyErrorThreshold ]
					);
					// Back off for a period of time before rechecking.
					$ttl = $failoverDuration;
					$this->recordFailover( 'siteverify_errors' );
					return 0;
				}

				return 1;
			},
			[
				'lockTSE' => 2,
				// Default to assuming availability while the value is regenerated
				'busyValue' => 1,
			]
		);

		$timer->setLabel( 'result', $this->isAvailable ? 'true' : 'false' )->stop();
		return $this->isAvailable;
	}

	private function recordFailover( string $reason ): void {
		$this->statsFactory->withComponent( 'ConfirmEdit' )
			->getCounter( 'hcaptcha_enterprise_failover_total' )
			->setLabel( 'reason', $reason )
			->increment();
	}

}
