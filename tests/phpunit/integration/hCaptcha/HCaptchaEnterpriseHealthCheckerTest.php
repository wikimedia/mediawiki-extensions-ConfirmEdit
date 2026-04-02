<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration\hCaptcha;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\HCaptchaEnterpriseHealthChecker;
use MediaWikiIntegrationTestCase;
use Psr\Log\NullLogger;
use Wikimedia\ObjectCache\BagOStuff;
use Wikimedia\ObjectCache\HashBagOStuff;
use Wikimedia\ObjectCache\WANObjectCache;
use Wikimedia\Stats\StatsFactory;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\HCaptchaEnterpriseHealthChecker
 */
class HCaptchaEnterpriseHealthCheckerTest extends MediaWikiIntegrationTestCase {

	public function testIncrementSiteverifyApiErrorCountAboveThreshold() {
		$statsHelper = StatsFactory::newUnitTestingHelper()->withComponent( 'ConfirmEdit' );
		$services = $this->getServiceContainer();
		$healthChecker = new HCaptchaEnterpriseHealthChecker(
			new ServiceOptions(
				HCaptchaEnterpriseHealthChecker::CONSTRUCTOR_OPTIONS,
				$services->getMainConfig()
			),
			new NullLogger(),
			$services->getObjectCacheFactory()->getLocalClusterInstance(),
			$services->getMainWANObjectCache(),
			$statsHelper->getStatsFactory(),
		);
		for ( $i = 1; $i <= 10; $i++ ) {
			$healthChecker->incrementSiteVerifyApiErrorCount();
		}
		$this->assertFalse( $healthChecker->isAvailable() );
		$this->assertSame( 1, $statsHelper->count(
			'hcaptcha_enterprise_failover_total{reason="siteverify_errors"}' )
		);
	}

	public function testIncrementSiteverifyApiErrorCountBelowThreshold() {
		$services = $this->getServiceContainer();
		$healthChecker = new HCaptchaEnterpriseHealthChecker(
			new ServiceOptions(
				HCaptchaEnterpriseHealthChecker::CONSTRUCTOR_OPTIONS,
				$services->getMainConfig()
			),
			new NullLogger(),
			$services->getObjectCacheFactory()->getLocalClusterInstance(),
			$services->getMainWANObjectCache(),
			$services->getStatsFactory(),
		);
		$healthChecker->incrementSiteVerifyApiErrorCount();
		$this->assertTrue( $healthChecker->isAvailable() );
	}

	public function testInProcessCache() {
		/** @var HCaptchaEnterpriseHealthChecker $healthChecker */
		$healthChecker = $this->getServiceContainer()->getService( 'HCaptchaEnterpriseHealthChecker' );
		$wrapper = TestingAccessWrapper::newFromObject( $healthChecker );
		$wrapper->isAvailable = true;
		// This check would fail if the process cache wasn't hit.
		$this->assertTrue( $healthChecker->isAvailable() );
	}

	public function testCachedUnavailable() {
		$bag = new HashBagOStuff();
		$wanObjectCacheMock = new WANObjectCache( [
			'cache' => $bag,
		] );
		// Simulate a previous health check that cached unavailability (e.g.
		// the callback returned 0 with a 10-minute TTL).
		$wanObjectCacheMock->set(
			$wanObjectCacheMock->makeGlobalKey( 'confirmedit-hcaptcha-available' ),
			0
		);
		$statsHelper = StatsFactory::newUnitTestingHelper()->withComponent( 'ConfirmEdit' );
		$healthChecker = new HCaptchaEnterpriseHealthChecker(
			new ServiceOptions(
				HCaptchaEnterpriseHealthChecker::CONSTRUCTOR_OPTIONS,
				$this->getServiceContainer()->getMainConfig()
			),
			new NullLogger(),
			$this->createNoOpMock( BagOStuff::class ),
			$wanObjectCacheMock,
			$statsHelper->getStatsFactory(),
		);
		$this->assertFalse( $healthChecker->isAvailable() );
		$this->assertSame( 1, $statsHelper->count(
			'hcaptcha_enterprise_health_checker_is_available_seconds{result="false"}' )
		);
	}

	public function testHealthyWhenNoErrors() {
		$services = $this->getServiceContainer();
		$healthChecker = new HCaptchaEnterpriseHealthChecker(
			new ServiceOptions(
				HCaptchaEnterpriseHealthChecker::CONSTRUCTOR_OPTIONS,
				$services->getMainConfig()
			),
			new NullLogger(),
			$services->getObjectCacheFactory()->getLocalClusterInstance(),
			$services->getMainWANObjectCache(),
			$services->getStatsFactory(),
		);
		$this->assertTrue( $healthChecker->isAvailable() );
	}
}
