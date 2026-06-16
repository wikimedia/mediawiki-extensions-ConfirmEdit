<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration\Hooks\Handlers;

use MediaWiki\Config\HashConfig;
use MediaWiki\Extension\ConfirmEdit\hCaptcha\Services\HCaptchaOutput;
use MediaWiki\Extension\ConfirmEdit\Hooks\Handlers\RLRegisterModulesHandler;
use MediaWiki\ResourceLoader\Context;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\Hooks\Handlers\RLRegisterModulesHandler
 */
class RLRegisterModulesHandlerTest extends MediaWikiIntegrationTestCase {
	public function testGetHCaptchaConfig(): void {
		$mockHCaptchaOutput = $this->createMock( HCaptchaOutput::class );
		$mockHCaptchaOutput->method( 'getHCaptchaApiUrl' )
			->with( 'nb' )
			->willReturn( 'https://example.com/hcaptcha.js?hl=nb' );
		$this->setService( 'HCaptchaOutput', $mockHCaptchaOutput );

		$config = [
			'HCaptchaSiteKey' => 'abc',
			'HCaptchaEnterprise' => true,
			'HCaptchaCustomThemeSupported' => false,
			'HCaptchaSecureEnclave' => true,
			'HCaptchaApiUrlIntegrityHash' => 'abcdef',
			'HCaptchaEnabledInMobileFrontend' => false,
			'HCaptchaInvisibleMode' => true,
		];

		$resourceLoaderContext = $this->createMock( Context::class );
		$resourceLoaderContext->method( 'getLanguage' )
			->willReturn( 'nb' );

		$this->assertArrayEquals(
			array_merge( $config, [ 'HCaptchaApiUrl' => 'https://example.com/hcaptcha.js?hl=nb' ] ),
			RLRegisterModulesHandler::getHCaptchaConfig( $resourceLoaderContext, new HashConfig( $config ) ),
			false,
			true
		);
	}
}
