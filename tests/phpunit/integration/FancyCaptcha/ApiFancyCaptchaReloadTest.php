<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\Tests\Integration\FancyCaptcha;

use MediaWiki\Extension\ConfirmEdit\FancyCaptcha\ApiFancyCaptchaReload;
use MediaWiki\Extension\ConfirmEdit\FancyCaptcha\FancyCaptcha;
use MediaWiki\Extension\ConfirmEdit\Store\CaptchaStore;
use MediaWiki\Extension\ConfirmEdit\Tests\Integration\CaptchaTestHelperTrait;
use MediaWiki\MainConfigNames;
use MediaWiki\Tests\Api\ApiTestCase;
use UnderflowException;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\FancyCaptcha\ApiFancyCaptchaReload
 * @group Database
 */
class ApiFancyCaptchaReloadTest extends ApiTestCase {
	use CaptchaTestHelperTrait;

	protected function setUp(): void {
		parent::setUp();
		self::clearCaptchaFactoryGlobalInstances();

		$newAPIModules = $this->getServiceContainer()->getMainConfig()->get( MainConfigNames::APIModules );
		$newAPIModules['fancycaptchareload'] = ApiFancyCaptchaReload::class;
		$this->overrideConfigValue( MainConfigNames::APIModules, $newAPIModules );
	}

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		self::clearCaptchaFactoryGlobalInstances();
	}

	public function testExecuteWhenNoCaptchaImages() {
		// Create a captcha directory with no images
		$captchaDirectory = $this->getNewTempDirectory();
		$this->overrideConfigValue( 'CaptchaClass', FancyCaptcha::class );
		$this->overrideConfigValue( 'CaptchaDirectory', $captchaDirectory );
		$this->overrideConfigValue( 'CaptchaStorageDirectory', 'subfolder' );

		// Expect that the API throws that no images could be found
		$this->expectException( UnderflowException::class );
		$this->expectExceptionMessage( 'Ran out of captcha images' );
		$this->doApiRequest( [ 'action' => 'fancycaptchareload' ] );
	}

	public function testExecuteForSuccess() {
		$captchaDirectory = $this->getNewTempDirectory();
		$this->overrideConfigValue( 'CaptchaClass', FancyCaptcha::class );
		$this->overrideConfigValue( 'CaptchaDirectory', $captchaDirectory );
		$this->overrideConfigValue( 'CaptchaStorageDirectory', 'subfolder' );
		$this->overrideConfigValue( 'CaptchaSecret', 'secret' );

		// Create one captcha file in the $captchaDirectory folder with a defined hash and salt in the filename.
		$captchaImageFilename = $captchaDirectory . "/image_abcdef_01234.png";
		file_put_contents( $captchaImageFilename, 'abc' );

		// Run the API
		[ $result ] = $this->doApiRequest( [ 'action' => 'fancycaptchareload' ] );

		// Expect that the index returned by the API references the file in our captcha image directory
		$this->assertArrayHasKey( 'fancycaptchareload', $result );
		$this->assertArrayHasKey( 'index', $result['fancycaptchareload'] );
		$newIndex = $result['fancycaptchareload']['index'];

		$captchaInfo = CaptchaStore::get()->retrieve( $newIndex );
		$this->assertArrayContains( [ 'salt' => 'abcdef', 'hash' => '01234' ], $captchaInfo );
	}
}
